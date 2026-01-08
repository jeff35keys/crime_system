<?php
   $servername = "localhost";
   $username = "root";
   $password = "";  // ← Remove your real password!
   $database = "crime_system";
   
   $conn = mysqli_connect($servername, $username, $password, $database);
   
   if (!$conn) {
       die("Database connection failed: " . mysqli_connect_error());
   }
   ?>