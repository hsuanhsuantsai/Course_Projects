# Dish Recommendation System
* A web service offering dish recommendation from MySQL database
* Users are able to order the recommendation sets directly
* Supports user registration, online order business, dish search  

## Live example
[Example Here](https://mpcs53001.cs.uchicago.edu/~hsuantsai/final.html)
## Note
* I don't want to show error messages to users, so I don't put `$conn` in `mysqli_error()`   
(i.e. `mysqli_error($conn)`) 

