const express = require('express');
const router = express.Router();
const authController = require('../controllers/authController');

router.post('/signup', authController.signup); // Must be a function
router.post('/login', authController.login);   // Must be a function

module.exports = router;