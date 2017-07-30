//20170130
//Flora Tsai

#include "mnist.h"
#include "distance.h"
#include <stdio.h>
#include <stdlib.h>
#include <math.h>

static
double reduced_distance_func(distance_t dthis, const mnist_image_handle a, 
	const mnist_image_handle b, const mnist_dataset_handle handle){
	
	int size=mnist_image_total_size(handle);
	const int *A=mnist_image_data(a);
	const int *B=mnist_image_data(b);
	double suma=0;
	double sumb=0;
	for (int i=0; i<size; i++){
		suma+=A[i];
		sumb+=B[i];
	}
	return fabs(suma-sumb);
}

const char * reduced_distance_describe(distance_t dthis){
	return "reduced";
}

distance_t reduced_func_create(){
	distance_t d=malloc(sizeof(DISTANCE_T));
	d->func = reduced_distance_func;
	d->describe = reduced_distance_describe;

	return d;
}
