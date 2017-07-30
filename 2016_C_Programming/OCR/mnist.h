//20170130
//Flora Tsai

//modified from HW5 and added some functions needed


#ifndef MNIST_H
#define MNIST_H

#include <stdbool.h>

struct mnist_dataset_t;

/// Define struct mnist_dataset_t in your mnist.c file
typedef struct mnist_dataset_t * mnist_dataset_handle;

#define MNIST_DATASET_INVALID ((mnist_dataset_handle) 0)

/// Define struct mnist_image_t in your mnist.c file
struct mnist_image_t;
typedef struct mnist_image_t * mnist_image_handle;

#define MNIST_IMAGE_INVALID ((mnist_image_handle) 0)

/// Open the given MNIST formatted dataset;
/// Returns MNIST_INVALID if the dataset could not be opened,
/// and a mnist_dataset_handle on success.
/// The handle must be freed using mnist_free().
/// 
/// For dataset 'name', the following files must exist:
///   - name-labels-idx1-ubyte
///   - name-images-idx3-ubyte
/// in other words, to open the dataset represented by the files above,
/// the user would call mnist_open("name")
///
/// The open function checks the 'magic number' at the beginning of the file
/// and returns MNIST_DATASET_INVALID if the number does not match
/// expectations.
mnist_dataset_handle mnist_open (const char * name);


/// Create a new empty dataset.
/// This only creates the in-memory representation of the dataset.
/// No actual files need to be created.
/// x and y refer to the image size for this dataset.
/// Returns MNIST_DATASET_INVALID if an error occurred.
///
/// In case of success, the resulting handle needs to be freed
/// using mnist_free.
mnist_dataset_handle mnist_create(unsigned int x, unsigned int y);

/// Free the memory associated with the handle
void mnist_free (mnist_dataset_handle handle);


/// Return the number of images in the dataset
/// Returns <0 if handle is equal to MNIST_DATASET_INVALID
int mnist_image_count (const mnist_dataset_handle handle);

/// Return the size of each image in the dataset; Stores the size in X and Y.
/// Sets *x and *y to 0 if handle == MNIST_DATASET_INVALID
void mnist_image_size (const mnist_dataset_handle handle,
                       int * x, int * y);

// Get total size of pixel array, which is x*y.
int mnist_image_total_size(const mnist_dataset_handle handle);

/// Return a handle to the first image in the dataset;
/// Note: there is no requirement to free the returned handle.
/// If there are no images in the dataset, or if handle ==
/// MNIST_DATASET_INVALID, or any other error occurs,
/// return MNIST_IMAGE_INVALID
mnist_image_handle mnist_image_begin (const mnist_dataset_handle handle);

/// Return a pointer to the data for the image. The data should not be copied
/// and the user of the data should not modify or free it. The return pointer
/// should point to image_size_x * image_size_y bytes, in the same order as
/// specified in the MNIST file format.
/// The pointer must remain valid even after mnist_image_next() is called.
/// However, when mnist_free() is called, all pointers returned by this
/// function become invalid as well.
const int * mnist_image_data (const mnist_image_handle h);

/// Obtain the label of the image.
/// Return <0 if handle is equal to MNIST_IMAGE_INVALID.
int mnist_image_label (const mnist_image_handle h);

/// Return the handle of the next image in the dataset, or MNIST_IMAGE_INVALID
/// if there are no more images.
mnist_image_handle mnist_image_next (const mnist_image_handle h);


/// Add a new image *after* the image represented by mnist_image_handle
/// Returns a handle referring to the new image
/// 
///  If i == MNIST_IMAGE_INVALID, add as the very first image of the
///             dataset (without replacing an already existing image).
///
///  imagedata: point to the image data. The data is *copied* into
///             the data structure (i.e. the imagedata pointer doesn't
///             need to remain valid after the call returns.
///
///  x:         number of columns in the image
///  y:         number of rows in the image.
///
/// returns MNIST_IMAGE_INVALID if there was an issue.
///  (for example, x and y don't match the sizes for which the dataset
///   was create).
///
mnist_image_handle mnist_image_add_after (mnist_dataset_handle h,
      mnist_image_handle i,
      const unsigned char * imagedata, unsigned int x, unsigned int y,
      unsigned int label);

/// Persist the specified dataset to file.
///
/// Name follows the same convention as for mnist_open.
/// i.e. mnist_save(h, "name"); should create two files (a label and
/// image file) with appropriate
/// file names.
///
/// If successful, returns true. If any error happens (including I/O)
/// returns false.
///
/// If the function fails, there should be no memory leaks.
///
/// If the dataset already exists, this function will overwrite it without
/// warning.
bool mnist_save(const mnist_dataset_handle h, const char * filename);

//Reservoir sampling
mnist_dataset_handle mnist_sample(const mnist_dataset_handle train, int N);

//copy an image, not only create a pointer to an image.
//take x and y as the size of the target image
//leave next of the new image_handle point to NULL
mnist_image_handle mnist_copy_image(const mnist_image_handle img, int x, int y);

#endif
