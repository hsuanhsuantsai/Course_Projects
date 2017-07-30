//20170130
//Flora Tsai

#include "mnist.h"
#include "distance.h"
#include <stdio.h>
#include <string.h>



distance_t create_distance_function(const char *schemename){
	if (!strcmp(schemename,"euclid"))
		return euclid_func_create();
	else if(!strcmp(schemename,"reduced"))
		return reduced_func_create();
	else if(!strcmp(schemename,"downsample"))
		return downsample_func_create();
	else if(!strcmp(schemename,"crop"))
		return crop_func_create();
	else if(!strcmp(schemename,"threshold"))
		return threshold_func_create();
	return (distance_t) 0;
}

char * LIST(int *s){
	*s=5;
	return "euclid reduced downsample crop threshold";
}


