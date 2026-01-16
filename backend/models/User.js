const mongoose = require('mongoose');
const userSchema = new mongoose.Schema({
  firstName: {
    type: String,
    required: [true, "First name is required"]
  },
  lastName: {
    type: String,
    required: [true, "Last name is required"]
  },
  email: {
    type: String,
    required: [true, "Email is required"],
    unique: true,
    validate: [validator.isEmail, "Please provide a valid email"]
  },
  employeeId: {
    type: String,
    required: [true, "Employee ID is required"],
    unique: true
  },
  password: {
    type: String,
    required: [true, "Password is required"],
    minlength: 8,
    validate: {
      validator: function(pass) {
        return /^(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[!@#$%^&*]).{8,}$/.test(pass);
      },
      message: "Password must contain at least 1 uppercase, 1 lowercase, 1 number, and 1 special character"
    }
  }
});