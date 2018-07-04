<?php

namespace SnooPHP\Model;

/**
 * A node ispired by the concept of node in the facebook api graph
 * 
 * Nodes take even further the concept of relationship between models of the application
 * 
 * A node has multiple fields and edges:
 * - fields are primitive properties of the underlying model (numbers, strings, ...)
 * - edges are connections to other nodes of the graph, usually in the form of ids (integer values)
 * 
 * All requests should have a starting node and a set (possibly empty) of expanded edges:
 * `GET /user/{login}?edges=relationship|friends|achievements`
 * 
 * Edges can be nested:
 * 
 * `GET /user{login}?edges=relationship|friends(relationship|friends|achievements)`
 * 
 * The Node class exposes a method @see expand() which MUST be overriden to expand edges of the current node.
 * 
 * Note that the Node class extends the SnooPHP class, so you can perfom classic database operations.
 * It is also compatible with Collection (i.e. collections can be collections of nodes)
 */
abstract class Node extends Model
{
	/**
	 * @var string $edgePrefix prefix of edge connection methods
	 */
	protected static $edgePrefix = "e_";

	/**
	 * Expand edges of this node
	 * 
	 * @param string|array $edges set (or string) of nodes to expand
	 * 
	 * @return static expanded node
	 */
	public function expand($edges = [])
	{
		// Get edges to expand
		$edges = is_array($edges) ? $edges : static::parseEdgesString($edges);
		if ($edges === false) return $this;

		foreach ($edges as $edge => $subedges)
		{
			$edgeConnection = static::$edgePrefix.$edge;
			if (method_exists($this, $edgeConnection))
			{
				$node = $this->$edgeConnection();

				// Call subedges
				if (is_a($node, "SnooPHP\Model\Node"))
					$node->expand($subedges);
				else if (is_a($node, "SnooPHP\Model\Collection"))
				{
					$node->each(function($subnode) use($subedges) {

						if (is_a($subnode, "SnooPHP\Model\Node"))
							$subnode->expand($subedges);
					});
					$node = $node->array();
				}

				$this->$edge = $node;
			}
		}

		return $this;
	}

	/**
	 * Takes a string of edges to expand and returns an array or a json string
	 * 
	 * @param string	$edges	string of edges
	 * @param bool		$json	if true return json string instead of array
	 * 
	 * @return array|string|false false if fails
	 */
	public static function parseEdgesString($edges, $json = false)
	{
		$parser = __DIR__."/graphparser";
		if (!file_exists($parser)) return false;

		$out = `$parser "$edges"`;
		return $json ? $out : from_json($out, true);
	}
}