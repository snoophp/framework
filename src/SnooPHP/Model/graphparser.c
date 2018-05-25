#include <stdio.h>
#include <stdlib.h>
#include <sys/time.h>

// Constants
#define VALID 				1
#define ERR_NO_INPUT		-1
#define ERR_INVALID_INPUT	-2

#define LOG_NONE	0
#define LOG_ALL		1

// Global variables
int cc = 0;
int logLevel = LOG_NONE;

// Automatons

/**
 * Entry point, recursive
 */
int S(char*);

/**
 * Parse a single edge followed by an optional nested block
 */
int E(char*);

/**
 * Parse an edge
 */
int W(char*);

// Helpers

/**
 * Return current character of input
 */
char curr(char*);

/**
 * Advance input string to next character
 */
void next();

/**
 * Log to stdout
 */
void _log(char*);

/**
 * Entry point
 */
int main(int argc, char **argv)
{
	if (argc < 2)
	{
		_log("error: no input found!");
		return ERR_NO_INPUT;
	}
	else
	{
		int result = 0;
		char *input = NULL;
		cc = 0;
		input = argv[1];

		if (input != NULL)
		{
			printf("{");
			result = S(input);
			printf("}");
		}
		
		_log(result == VALID ? "string accepted\n" : "invalid input\n");

		return result;
	}
}

// Automatons

int S(char* input)
{
	_log("called S\n");
	if (E(input) == VALID)
	{
		if (curr(input) == '|' || curr(input) == ',')
		{
			printf(",");
			next();
			if (S(input) == VALID) return VALID;

			return ERR_INVALID_INPUT;
		}
		
		return VALID;
	}
	
	return ERR_INVALID_INPUT;
}

int E(char* input)
{
	_log("called E\n");
	printf("\"");
	if (W(input) == VALID)
	{
		printf("\":");
		if (curr(input) == '(')
		{
			printf("{");
			next();
			if (S(input) == VALID)
			{
				if (curr(input) == ')')
				{
					printf("}");
					next();
					return VALID;
				}
			}

			return ERR_INVALID_INPUT;
		}


		printf("{}");
		return VALID;
	}

	// Sink
	return ERR_INVALID_INPUT;
}

int W(char* input)
{
	_log("called W\n");

	if ((curr(input) >= 'A' && curr(input) <= 'Z') || (curr(input) >= 'a' && curr(input) <= 'z') || curr(input) == '_')
	{
		printf("%c", curr(input));
		next();
		while((curr(input) >= 'A' && curr(input) <= 'Z') || (curr(input) >= 'a' && curr(input) <= 'z') || curr(input) == '_' || (curr(input) >= '0' && curr(input) <= '9'))
		{
			printf("%c", curr(input));
			next();
		}

		return VALID;
	}

	// Sink
	return ERR_INVALID_INPUT;
}

// Helpers

char curr(char *input)
{
	if (input == NULL) exit(ERR_NO_INPUT);
	return input[cc];
}

void next()
{
	cc++;
}

void _log(char* text)
{
	if (logLevel == LOG_ALL) printf("%s", text);
}
