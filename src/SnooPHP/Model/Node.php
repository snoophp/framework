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
	 * Expand edges of this node
	 * 
	 * @param string|array|null $edges set (or string) of nodes to expand or null to expand all (use it carefully)
	 * 
	 * @return Node expanded node
	 */
	abstract public function expand($edges = null);

	/**
	 * Takes a string of edges to expand and returns an array
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

		$out = `{$parser} "{$edges}"`;
		return $json ? $out : from_json($out, true);
	}
}