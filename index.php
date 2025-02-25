<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BabyCubs</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        /* Existing styles remain the same, with these additions */
        .navbar-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 2rem;
        }

        .search-cart {
            display: flex;
            align-items: center;
            gap: 15px;
            position: relative;
        }

        .profile-icon-container {
            position: relative;
            display: inline-block;
        }

        .profile-icon {
            font-size: 1.2rem;
            color: #333;
            background-color: #f1f1f1;
            border-radius: 50%;
            width: 36px;
            height: 36px;
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            transition: background-color 0.3s, color 0.3s;
        }

        .profile-icon:hover {
            background-color: #007bff;
            color: white;
        }

        .profile-dropdown {
            display: none;
            position: absolute;
            background-color: #f1f1f1;
            min-width: 160px;
            box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
            z-index: 1;
            right: 0;
            border-radius: 5px;
            overflow: hidden;
        }

        .profile-dropdown a {
            color: #333;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
            transition: background-color 0.3s;
        }

        .profile-dropdown a:hover {
            background-color: #ddd;
        }

        .profile-icon-container:hover .profile-dropdown {
            display: block;
        }



        body {
            font-family: Arial, sans-serif;
            background-color: rgb(255, 234, 249);
            line-height: 1.6;
        }

        .container {
            width: 90%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
        }

        /* Navigation */
        .navbar {
            background: rgba(255, 255, 255, 0.95);
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            padding: 1rem 0;
        }

        .navbar-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .navbar-brand {
            font-weight: bold;
            font-size: 2.5rem;
            text-decoration: none;
            color: blue;
        }

        .nav-menu {
            display: flex;
            list-style: none;
            align-items: center;
        }

        .nav-item {
            margin-left: 1.5rem;
        }

        .nav-link {
            text-decoration: none;
            color: #333;
            transition: color 0.3s ease;
        }

        .nav-link:hover {
            color: #007bff;
        }

        .search-cart {
            display: flex;
            gap: 20px;
            align-items: center;
        }

        .search-box {
            padding: 8px 15px;
            border: 1px solid #ddd;
            border-radius: 20px;
            width: 200px;
        }

        .button {
            text-decoration: none;
            color: #333;
            padding: 8px 15px;
            border: 1px solid #ddd;
            border-radius: 20px;
            transition: all 0.3s ease;
        }

        .button:hover {
            background-color: #007bff;
            color: white;
            border-color: #007bff;
        }

        /* Hero Section */
        .hero-section {
            position: relative;
            height: 100vh;
            overflow: hidden;
        }

        .hero-video {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: -1;
        }

        .hero-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.3);
            display: flex;
            align-items: center;
        }

        .hero-content {
            width: 50%;
            padding: 2rem;
        }

        .hero-title {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }

        .hero-text {
            font-size: 1.2rem;
            margin-bottom: 2rem;
        }

        .btn {
            display: inline-block;
            padding: 1rem 2rem;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }

        .btn:hover {
            background-color: #0056b3;
        }

        /* Categories Section */
        .baby {
            padding: 5rem 0;
        }

        .section-title {
            text-align: center;
            margin-bottom: 3rem;
        }

        .categories-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 2rem;
        }

        .category-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            transition: transform 0.3s ease;
            cursor: pointer;
        }

        .category-card:hover {
            transform: translateY(-10px);
        }

        .card-img {
            width: 100%;
            height: 250px;
            object-fit: cover;
        }

        .card-body {
            padding: 1.5rem;
            text-align: center;
        }

        /* Products Section */
        .products {
            padding: 5rem 0;
            background-color: #f8f9fa;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 2rem;
        }

        .product-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .product-card:hover {
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }

        .product-img {
            width: 100%;
            height: 250px;
            object-fit: cover;
        }

        /* Newsletter Section */
        .newsletter {
            padding: 5rem 0;
            text-align: center;
        }

        .newsletter-form {
            display: flex;
            max-width: 500px;
            margin: 2rem auto;
            gap: 1rem;
        }

        .newsletter-input {
            flex: 1;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        /* Footer */
        .footer {
            background-color: #f8f9fa;
            padding: 3rem 0;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 2rem;
        }

        .footer-links {
            list-style: none;
        }

        .footer-link {
            text-decoration: none;
            color: #333;
            margin-bottom: 0.5rem;
            display: block;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .nav-menu {
                display: none;
            }

            .hero-content {
                width: 100%;
                text-align: center;
            }

            .categories-grid {
                grid-template-columns: 1fr;
            }

            .products-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .footer-grid {
                grid-template-columns: 1fr;
                text-align: center;
            }
        }

        /* Animation */
        @keyframes floating {
            0% { transform: translate(0, 0); }
            50% { transform: translate(0, 15px); }
            100% { transform: translate(0, 0); }
        }

        .floating {
            animation: floating 3s ease-in-out infinite;
        }
        .profile-icon-container {
    position: relative;
    display: inline-block;
}

.profile-icon {
    font-size: 1.2rem;
    color: #333;
    background-color:rgb(232, 123, 123);
    border-radius: 50%;
    width: 36px;
    height: 36px;
    display: flex;
    justify-content: center;
    align-items: center;
    cursor: pointer;
    transition: background-color 0.3s, color 0.3s;
}

.profile-icon:hover {
    background-color: #007bff;
    color: white;
}

.profile-dropdown {
    display: none;
    position: absolute;
    background-color: #f1f1f1;
    min-width: 160px;
    box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
    z-index: 1;
    right: 0;
    border-radius: 5px;
    overflow: hidden;
}

.profile-dropdown a {
    color: #333;
    padding: 12px 16px;
    text-decoration: none;
    display: block;
    transition: background-color 0.3s;
}

.profile-dropdown a:hover {
    background-color: #ddd;
}

.profile-icon-container:hover .profile-dropdown {
    display: block;
}
    </style>
</head>
<body>
<nav class="navbar">
        <div class="container navbar-content">
            <a href="#" class="navbar-brand">BabyCubs</a>
            <ul class="nav-menu">
                <li class="nav-item"><a href="#home" class="nav-link">Home</a></li>
                <li class="nav-item"><a href="#baby" class="nav-link">Baby</a></li>
                <li class="nav-item"><a href="#toddler" class="nav-link">Toddler</a></li>
                <li class="nav-item"><a href="#kids" class="nav-link">Kids</a></li>
                <li class="nav-item"><a href="#brands" class="nav-link">Brands</a></li>
            </ul>
            <div class="search-cart">
                <input type="search" placeholder="Search products..." class="search-box">
                <a href="#" class="nav-link">🛒</a>
                <div class="profile-icon-container">
    <?php if (isset($_SESSION['username'])): ?>
        <div class="profile-icon">
    <i class="fas fa-user"></i>
</div>
<span><?php echo htmlspecialchars($_SESSION['username']); ?></span>

        <div class="profile-dropdown">
            <a href="profile.php"><i class="fas fa-user-circle"></i> Profile</a>
            <a href="orders.php"><i class="fas fa-shopping-bag"></i> Orders</a>
            <a href="favorites.php"><i class="fas fa-heart"></i> Favorites</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    <?php else: ?>
        <div class="profile-icon">
            <i class="fas fa-user"></i>
        </div>
        <div class="profile-dropdown">
            <a href="login.php">Login</a>
            <a href="signup.php">Sign Up</a>
        </div>
    <?php endif; ?>
</div>
            </div>
        </div>
    </nav>


    <section id="home" class="hero-section">
        <video class="hero-video" autoplay muted loop>
            <source src="baby.mp4" type="video/mp4">
        </video>
        <div class="hero-overlay">
            <div class="container">
                <div class="hero-content">
                    <h1 class="hero-title animate__animated animate__fadeInLeft">Welcome to<br> BabyCubs</h1>
                    <p class="hero-text animate__animated animate__fadeInLeft animate__delay-1s">Discover adorable and comfortable clothing for your little ones</p>
                    <a href="product_view.php" class="btn animate__animated animate__fadeInUp animate__delay-2s">Shop Now</a>
                </div>
            </div>
        </div>
    </section>

    <section id="baby" class="baby">
        <div class="container">
            <h2 class="section-title">Shop For Baby</h2>
            <div class="categories-grid">
                <div class="category-card">
                    <a href="newborn.php">
                    <img src="./images/pic.jpg" class="card-img" alt="Clothing">
                     </a>
                    <div class="card-body">
                        <h5>New Born</h5>
                    </div>
                </div>
                <div class="category-card">
                <a href="">
                    <img src="./images/pic13.jpg" class="card-img" alt="Toys">
                 </a>
                    <div class="card-body">
                        <h5>Baby Boy</h5>
                    </div>
                </div>
                <div class="category-card">
                <a href="">
                    <img src="./images/pic20.jpg" class="card-img" alt="Care">
                </a>
                    <div class="card-body">
                        <h5>Baby Girl</h5>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="toddler" class="products">
        <div class="container">
            <h2 class="section-title">Toddler Products</h2>
            <div class="products-grid">
                <div class="product-card">
                    <img src="./images/pic22.jpg" class="product-img" alt="Product 1">
                    <div class="card-body">
                        <h5>Boy</h5>
                        <p>$29.99</p>
                        <a href="#" class="btn">Add to Cart</a>
                    </div>
                </div>
                <div class="product-card">
                    <img src="./images/pic21.jpg" class="product-img" alt="Product 1">
                    <div class="card-body">
                        <h5>Girl</h5>
                        <p>$29.99</p>
                        <a href="#" class="btn">Add to Cart</a>
                    </div>
                </div>
                <div class="product-card">
                    <img src="./images/ass.jpg" class="product-img" alt="Product 1">
                    <div class="card-body">
                        <h5>Accessories</h5>
                        <p>$29.99</p>
                        <a href="#" class="btn">Add to Cart</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="kids" class="products">
        <div class="container">
            <h2 class="section-title">Kids Products</h2>
            <div class="products-grid">
                <div class="product-card">
                    <img src="./images/pic18.jpg" class="product-img" alt="Product 1">
                    <div class="card-body">
                        <h5>Boy</h5>
                        <p>$29.99</p>
                        <a href="#" class="btn">Add to Cart</a>
                    </div>
                </div>
                <div class="product-card">
                    <img src="./images/pic17.jpg" class="product-img" alt="Product 1">
                    <div class="card-body">
                        <h5>Girl</h5>
                        <p>$29.99</p>
                        <a href="#" class="btn">Add to Cart</a>
                    </div>
                </div>
                <div class="product-card">
                    <img src="./images/pic19.jpg" class="product-img" alt="Product 1">
                    <div class="card-body">
                        <h5>Fashion</h5>
                        <p>$29.99</p>
                        <a href="#" class="btn">Add to Cart</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="brands" class="brands">
        <div class="container">
            <h2 class="section-title">Brands</h2>
            <div class="products-grid">
                <div class="product-card">
                    <img src="./images/pic29.jpg" class="product-img" alt="Product 1">
                    <div class="card-body">
                        <h5>Mackly</h5>
                        <p>$29.99</p>
                        <a href="#" class="btn">Add to Cart</a>
                    </div>
                </div>
                <div class="product-card">
                    <img src="./images/pic30.jpg" class="product-img" alt="Product 1">
                    <div class="card-body">
                        <h5>FirstCry</h5>
                        <p>$29.99</p>
                        <a href="#" class="btn">Add to Cart</a>
                    </div>
                </div>
                <div class="product-card">
                    <img src="./images/pic31.jpg" class="product-img" alt="Product 1">
                    <div class="card-body">
                        <h5>Ed-a Mamma</h5>
                        <p>$29.99</p>
                        <a href="#" class="btn">Add to Cart</a>
                    </div>
                </div>
            </div>
        </div>
    </section>





    <section class="newsletter">
        <div class="container">
            <h3>Subscribe to Our Newsletter</h3>
            <p>Get updates about new products and special offers!</p>
            <form class="newsletter-form">
                <input type="email" class="newsletter-input" placeholder="Enter your email">
                <button type="submit" class="btn">Subscribe</button>
            </form>
        </div>
    </section>

    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div>
                    <h5>BabyCubs</h5>
                    <p>Your one-stop shop for all baby needs</p>
                </div>
                <div>
                    <h5>Quick Links</h5>
                    <ul class="footer-links">
                        <li><a href="#" class="footer-link">About Us</a></li>
                        <li><a href="#" class="footer-link">Contact</a></li>
                        <li><a href="#" class="footer-link">Shipping Policy</a></li>
                        <li><a href="#" class="footer-link">Returns</a></li>
                    </ul>
                </div>
                <div>
                    <h5>Contact Us</h5>
                    <ul class="footer-links">
                        <li>Email: info@babycubs.com</li>
                        <li>Phone: (555) 123-4567</li>
                        <li>Address: 123 Baby Street</li>
                    </ul>
                </div>
            </div>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const elements = document.querySelectorAll('.category-card, .product-card');
            
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('animate__animated', 'animate__fadeInUp');
                    }
                });
            });

            elements.forEach(element => observer.observe(element));
        });
        document.addEventListener("DOMContentLoaded", function () {
    const profileIcon = document.querySelector(".profile-icon");
    const profileDropdown = document.querySelector(".profile-dropdown");

    profileIcon.addEventListener("click", function (event) {
        event.stopPropagation();
        profileDropdown.style.display = (profileDropdown.style.display === "block") ? "none" : "block";
    });

    document.addEventListener("click", function () {
        profileDropdown.style.display = "none";
    });

    profileDropdown.addEventListener("click", function (event) {
        event.stopPropagation();
    });
});

    </script>
</body>
</html> 