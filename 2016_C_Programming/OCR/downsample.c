//20170130
//Flora Tsai

#include "mnist.h"
#include "distance.h"
#include <stdio.h>
#include <stdlib.h>



static
double downsample_distance_func(distance_t dthis, const mnist_image_handle a, 
	const mnist_image_handle b, const mnist_dataset_handle handle){
	
	int size=mnist_image_total_size(handle);

	const int *A=mnist_image_data(a);
	const int *B=mnist_image_data(b);
	double sum=0;
	for (int i=0; i<size/2; i++)
		sum+=(A[2*i]-B[2*i])*(A[2*i]-B[2*i]);
	return sum;
}

const char * downsample_distance_describe(distance_t dthis){
	return "downsample";
}

distance_t downsample_func_create(){
	distance_t d=malloc(sizeof(DISTANCE_T));
	d->func = downsample_distance_func;
	d->describe = downsample_distance_describe;

	return d;
}
