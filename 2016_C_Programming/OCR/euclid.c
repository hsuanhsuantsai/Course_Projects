//20170130
//Flora Tsai

#include "mnist.h"
#include "distance.h"
#include <stdio.h>
#include <stdlib.h>


static
double euclid_distance_func(distance_t dthis, const mnist_image_handle a, 
	const mnist_image_handle b, const mnist_dataset_handle handle){
	
	int size=mnist_image_total_size(handle);

	const int *A=mnist_image_data(a);
	const int *B=mnist_image_data(b);
	double sum=0;
	for (int i=0; i<size; i++)
		sum+=(A[i]-B[i])*(A[i]-B[i]);
	return sum;
}

const char * euclid_distance_describe(distance_t dthis){
	return "euclid";
}

distance_t euclid_func_create(){
	distance_t d=malloc(sizeof(DISTANCE_T));
	d->func = euclid_distance_func;
	d->describe = euclid_distance_describe;

	return d;
}
