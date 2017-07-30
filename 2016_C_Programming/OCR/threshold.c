//20170130
//Flora Tsai

#include "mnist.h"
#include "distance.h"
#include <stdio.h>
#include <stdlib.h>
#include <math.h>

static
double threshold_distance_func(distance_t dthis, const mnist_image_handle a, 
	const mnist_image_handle b, const mnist_dataset_handle handle){
	
	int size=mnist_image_total_size(handle);
	const int *A=mnist_image_data(a);
	const int *B=mnist_image_data(b);
	double counta=0;
	double countb=0;
	for (int i=0; i<size; i++){
		if (A[i]>=127)
			counta++;
		if (B[i]>=127)
			countb++;
	}
	return fabs(counta-countb);
}

const char * threshold_distance_describe(distance_t dthis){
	return "threshold";
}

distance_t threshold_func_create(){
	distance_t d=malloc(sizeof(DISTANCE_T));
	d->func = threshold_distance_func;
	d->describe = threshold_distance_describe;

	return d;
}
