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
            position: relative;
        }

        .nav-link:hover {
            color: #007bff;
        }

        .nav-link::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: -5px;
            left: 0;
            background-color: #0077cc;
            transition: width 0.3s ease;
        }

        .nav-link:hover::after,
        .nav-link.active::after {
            width: 100%;
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

.categories-section {
    padding: 60px 0;
    background: linear-gradient(to bottom, #fff, #f8f9fa);
}

.category-block {
    margin-bottom: 60px;
    scroll-margin-top: 100px;
}

.category-title {
    font-size: 24px;
    color: #333;
    margin-bottom: 30px;
    text-align: center;
    position: relative;
    padding-bottom: 15px;
}

.category-title::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 50%;
    transform: translateX(-50%);
    width: 60px;
    height: 3px;
    background: linear-gradient(to right, #0077cc, #1a8cff);
    border-radius: 3px;
}

.category-card {
    background: white;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
}

.category-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
}

.category-card a {
    text-decoration: none;
    color: inherit;
}

.card-body {
    padding: 20px;
    text-align: center;
}

.card-body h5 {
    font-size: 18px;
    margin-bottom: 10px;
    color: #333;
}

.card-body p {
    font-size: 14px;
    color: #666;
    margin-bottom: 15px;
}

.shop-now-link {
    color: #0077cc;
    font-weight: 600;
    transition: color 0.3s ease;
}

.category-card:hover .shop-now-link {
    color: #005fa3;
}

.brand-card {
    background: white;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
}

.brand-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
}

html {
    scroll-behavior: smooth;
}

.search-container {
    flex-grow: 1;
    max-width: 600px;
    margin: 0 40px;
    position: relative;
}

.search-container form {
    display: flex;
    position: relative;
    width: 100%;
}

.search-container input {
    width: 100%;
    padding: 12px 20px;
    padding-right: 50px;
    border: 2px solid #e0e0e0;
    border-radius: 30px;
    font-size: 15px;
    transition: all 0.3s ease;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.search-container input:focus {
    border-color: #0077cc;
    box-shadow: 0 0 15px rgba(0,119,204,0.1);
    outline: none;
}

.search-btn {
    position: absolute;
    right: 5px;
    top: 50%;
    transform: translateY(-50%);
    height: 40px;
    width: 40px;
    padding: 0;
    background: linear-gradient(45deg, #0077cc, #1a8cff);
    color: white;
    border: none;
    border-radius: 50%;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

.search-btn:hover {
    background: linear-gradient(45deg, #005fa3, #0066cc);
    transform: translateY(-50%) scale(1.05);
}

.search-btn i {
    font-size: 16px;
}

@media (max-width: 768px) {
    .search-container {
        margin: 0 20px;
    }

    .search-container input {
        padding: 10px 15px;
        font-size: 14px;
    }

    .search-btn {
        height: 35px;
        width: 35px;
    }
}

.profile-icon-container .profile-dropdown a {
    display: flex;
    align-items: center;
    gap: 10px;
}

.profile-icon-container .profile-dropdown a i {
    width: 20px;
    text-align: center;
}

.profile-dropdown a:hover {
    background-color: #e9ecef;
    color: #0077cc;
}
    </style>
</head>
<body>
<nav class="navbar">
        <div class="container navbar-content">
            <a href="index.php" class="navbar-brand">BabyCubs</a>
            
            <div class="search-container">
                <form action="search.php" method="GET">
                    <input type="text" name="query" placeholder="Search products..." class="search-box">
                    <button type="submit" class="search-btn">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
            </div>

            <ul class="nav-menu">
                <li class="nav-item"><a href="#home" class="nav-link">Home</a></li>
                <li class="nav-item"><a href="#baby-collection" class="nav-link">Baby</a></li>
                <li class="nav-item"><a href="#toddler-collection" class="nav-link">Toddler</a></li>
                <li class="nav-item"><a href="#kids-collection" class="nav-link">Kids</a></li>
                <li class="nav-item"><a href="#featured-brands" class="nav-link">Brands</a></li>
            </ul>

            <div class="profile-icon-container">
                <?php if (isset($_SESSION['username'])): ?>
                    <div class="profile-icon">
                        <?php echo substr($_SESSION['username'], 0, 1); ?>
                    </div>
                    <div class="profile-dropdown">
                        <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
                        <a href="favorites.php"><i class="fas fa-heart"></i> Favorites</a>
                        <a href="cart.php"><i class="fas fa-shopping-cart"></i> Cart</a>
                        <a href="view_order_details.php"><i class="fas fa-heart"></i> My orders</a>
                        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </div>
                <?php else: ?>
                    <div class="profile-icon">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="profile-dropdown">
                        <a href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a>
                        <a href="signup.php"><i class="fas fa-user-plus"></i> Sign Up</a>
                    </div>
                <?php endif; ?>
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

    <section class="categories-section">
        <div class="container">
            <h2 class="section-title">Shop By Category</h2>
            
            <!-- Baby Category -->
            <div id="baby-collection" class="category-block">
                <h3 class="category-title">Baby Collection (0-24 months)</h3>
                <div class="products-grid">
                    <div class="category-card">
                        <a href="category.php?type=baby-boy">
                            <img src="./images/pic13.jpg" class="card-img" alt="Baby Boy">
                            <div class="card-body">
                                <h5>Baby Boy</h5>
                                <p>Clothing, Accessories & More</p>
                                <span class="shop-now-link">Shop Now →</span>
                            </div>
                        </a>
                    </div>
                    <div class="category-card">
                        <a href="category.php?type=baby-girl">
                            <img src="./images/pic20.jpg" class="card-img" alt="Baby Girl">
                            <div class="card-body">
                                <h5>Baby Girl</h5>
                                <p>Dresses, Sets & More</p>
                                <span class="shop-now-link">Shop Now →</span>
                            </div>
                        </a>
                    </div>
                    <div class="category-card">
                        <a href="category.php?type=newborn">
                            <img src="./images/pic.jpg" class="card-img" alt="Newborn">
                            <div class="card-body">
                                <h5>Newborn Essentials</h5>
                                <p>First Clothes & Care</p>
                                <span class="shop-now-link">Shop Now →</span>
                            </div>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Toddler Category -->
            <div id="toddler-collection" class="category-block">
                <h3 class="category-title">Toddler Collection (2-4 years)</h3>
                <div class="products-grid">
                    <div class="category-card">
                        <a href="category.php?type=toddler-boy">
                            <img src="./images/pic22.jpg" class="card-img" alt="Toddler Boy">
                            <div class="card-body">
                                <h5>Toddler Boy</h5>
                                <p>Casual & Party Wear</p>
                                <span class="shop-now-link">Shop Now →</span>
                            </div>
                        </a>
                    </div>
                    <div class="category-card">
                        <a href="category.php?type=toddler-girl">
                            <img src="./images/pic21.jpg" class="card-img" alt="Toddler Girl">
                            <div class="card-body">
                                <h5>Toddler Girl</h5>
                                <p>Dresses & Sets</p>
                                <span class="shop-now-link">Shop Now →</span>
                            </div>
                        </a>
                    </div>
                    <div class="category-card">
                        <a href="category.php?type=toddler-accessories">
                            <img src="./images/ass.jpg" class="card-img" alt="Accessories">
                            <div class="card-body">
                                <h5>Accessories</h5>
                                <p>Shoes, Bags & More</p>
                                <span class="shop-now-link">Shop Now →</span>
                            </div>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Kids Category -->
            <div id="kids-collection" class="category-block">
                <h3 class="category-title">Kids Collection (4-12 years)</h3>
                <div class="products-grid">
                    <div class="category-card">
                        <a href="category.php?type=kids-boy">
                            <img src="./images/pic18.jpg" class="card-img" alt="Kids Boy">
                            <div class="card-body">
                                <h5>Boys Fashion</h5>
                                <p>Trendy & Comfortable</p>
                                <span class="shop-now-link">Shop Now →</span>
                            </div>
                        </a>
                    </div>
                    <div class="category-card">
                        <a href="category.php?type=kids-girl">
                            <img src="./images/pic17.jpg" class="card-img" alt="Kids Girl">
                            <div class="card-body">
                                <h5>Girls Fashion</h5>
                                <p>Stylish Collections</p>
                                <span class="shop-now-link">Shop Now →</span>
                            </div>
                        </a>
                    </div>
                    <div class="category-card">
                        <a href="category.php?type=kids-fashion">
                            <img src="./images/pic19.jpg" class="card-img" alt="Kids Fashion">
                            <div class="card-body">
                                <h5>Fashion & Trends</h5>
                                <p>Latest Collections</p>
                                <span class="shop-now-link">Shop Now →</span>
                            </div>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Featured Brands -->
            <div id="featured-brands" class="category-block">
                <h3 class="category-title">Featured Brands</h3>
                <div class="products-grid">
                    <div class="brand-card">
                        <a href="category.php?brand=mackly">
                            <img src="./images/pic29.jpg" class="card-img" alt="Mackly">
                            <div class="card-body">
                                <h5>Mackly</h5>
                                <p>Premium Collection</p>
                                <span class="shop-now-link">Explore →</span>
                            </div>
                        </a>
                    </div>
                    <div class="brand-card">
                        <a href="category.php?brand=firstcry">
                            <img src="./images/pic30.jpg" class="card-img" alt="FirstCry">
                            <div class="card-body">
                                <h5>FirstCry</h5>
                                <p>Trusted Brand</p>
                                <span class="shop-now-link">Explore →</span>
                            </div>
                        </a>
                    </div>
                    <div class="brand-card">
                        <a href="category.php?brand=edamamma">
                            <img src="./images/pic31.jpg" class="card-img" alt="Ed-a-Mamma">
                            <div class="card-body">
                                <h5>Ed-a-Mamma</h5>
                                <p>Organic Collection</p>
                                <span class="shop-now-link">Explore →</span>
                            </div>
                        </a>
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

    document.addEventListener('DOMContentLoaded', function() {
        // Get all nav links
        const navLinks = document.querySelectorAll('.nav-link');
        
        // Add click event listeners for smooth scrolling
        navLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const targetId = this.getAttribute('href');
                const targetSection = document.querySelector(targetId);
                if (targetSection) {
                    targetSection.scrollIntoView({ behavior: 'smooth' });
                }
            });
        });

        // Highlight active section while scrolling
        window.addEventListener('scroll', function() {
            let current = '';
            const sections = document.querySelectorAll('.category-block');
            const heroSection = document.querySelector('.hero-section');

            // Check hero section first
            if (window.scrollY <= heroSection.offsetHeight) {
                current = 'home';
            } else {
                sections.forEach(section => {
                    const sectionTop = section.offsetTop;
                    if (window.scrollY >= sectionTop - 150) {
                        current = section.getAttribute('id');
                    }
                });
            }

            // Update active link
            navLinks.forEach(link => {
                link.classList.remove('active');
                if (link.getAttribute('href').substring(1) === current) {
                    link.classList.add('active');
                }
            });
        });
    });
    </script>
</body>
</html> 