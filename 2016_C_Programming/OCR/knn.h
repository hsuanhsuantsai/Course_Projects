//20170130
//Flora Tsai

#pragma once

#include <stdio.h>
#include <stdlib.h>
#include "mnist.h"
#include "distance.h"

double knn(mnist_dataset_handle train_h, mnist_dataset_handle test_h, 
		char *schemename, int k, int N);
