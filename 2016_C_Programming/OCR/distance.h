//20170130
//Flora Tsai

//for choosing different metric functions


#pragma once
#include "mnist.h"
#include <stdlib.h>

char * LIST(int *s);

struct DISTANCE_T;
typedef struct DISTANCE_T DISTANCE_T;
typedef struct DISTANCE_T *distance_t;


struct DISTANCE_T{
	double (*func) (distance_t this, const mnist_image_handle a, 
		const mnist_image_handle b, const mnist_dataset_handle handle);

	const char * (*describe) (distance_t this);
};



distance_t create_distance_function(const char *schemename);


static inline
double distance_func (distance_t this, const mnist_image_handle a, 
	const mnist_image_handle b, const mnist_dataset_handle handle){

	return this->func (this, a, b, handle);
}

static inline
const char * distance_describe(distance_t this){
	return this->describe(this);
}


distance_t euclid_func_create();
distance_t reduced_func_create();
distance_t downsample_func_create();
distance_t crop_func_create();
distance_t threshold_func_create();



