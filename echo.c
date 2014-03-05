/* This code is copyright Jack Johnson, all rights reserved.
 * Feel free to copy and modify this to your will. It is a skeleton C
 * program.
 * I found some of the constructs used here in a GNU manual, but they are well known and non-original.
 */

#include <stdio.h>

int main(int argc, char *argv[]) {
	int count; if (argc > 1) {
		for (count = 1; count < argc; count++) {
			printf("%s ", argv[count]);
		}
	} else {
		printf("I was not called with any arguments. :D");
	}
	printf("\n");
	return 0;
}
