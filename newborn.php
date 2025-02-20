<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>BabyCubs New Born</title>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: Arial, sans-serif;
      line-height: 1.6;
      background-color:rgb(176, 196, 244);
      color: #333;
    }

    /* Header styles */
    header {
      background-color: #fff;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
      padding: 1rem 2rem;
      position: sticky;
      top: 0;
      z-index: 1000;
    }
    .nav-item {
            margin-left: 1.5rem;
        }
    nav {
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    nav a {
      color: #0077b6;
      text-decoration: none;
      font-weight: bold;
    }

    #search-input {
      padding: 0.5rem;
      border: 1px solid #ccc;
      border-radius: 4px;
      margin-right: 0.5rem;
    }

    #search-btn {
      background-color: #0077b6;
      color: #fff;
      border: none;
      padding: 0.5rem 1rem;
      border-radius: 4px;
      cursor: pointer;
    }

    .right-nav {
      display: flex;
      align-items: center;
      gap: 1rem;
    }

    .user-actions button {
      background-color: transparent;
      border: none;
      color: #0077b6;
      cursor: pointer;
      font-weight: bold;
    }



    /* Dropdown menu styles */
    .dropdown {
      position: relative;
      display: inline-block;
    }

    .dropdown-content {
      display: none;
      position: absolute;
      background-color: #fff;
      min-width: 200px;
      box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
      z-index: 1000;
      border-radius: 4px;
      overflow: hidden;
    }

    .dropdown-content a {
      color: #333;
      padding: 0.75rem 1rem;
      text-decoration: none;
      display: block;
    }

    .dropdown-content a:hover {
      background-color: #f1f1f1;
    }

    .dropdown:hover .dropdown-content {
      display: block;
    }



    /* Main content styles */
    .banner {
      background-color:rgb(53, 177, 243);
      color: #fff;
      padding: 2rem;
      text-align: center;
    }

    .banner h1 {
      font-size: 2.5rem;
      margin-bottom: 0.5rem;
    }

    .banner button {
      background-color: #fff;
      color: #0077b6;
      border: 1px solid #fff;
      padding: 0.5rem 1rem;
      border-radius: 4px;
      cursor: pointer;
      font-weight: bold;
      margin-top: 1rem;
    }

    .products {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 2rem;
      padding: 2rem;
    }

    .product-card {
      background-color: #fff;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
      padding: 1rem;
      text-align: center;
      border-radius: 8px;
    }

    .product-card img {
      width: 100%;
      max-height: 300px;
      object-fit: cover;
      border-radius: 8px;
      margin-bottom: 1rem;
    }

    .product-card h3 {
      font-size: 1.2rem;
      margin-bottom: 0.5rem;
    }

    .product-card p {
      margin: 0.5rem 0;
      color: #555;
    }

    .nav-menu {
                display: none;
            }

    .add-to-cart {
      background-color: #0077b6;
      color: #fff;
      border: none;
      padding: 0.5rem 1rem;
      border-radius: 4px;
      cursor: pointer;
      font-weight: bold;
    }

    footer {
      background-color: #333;
      color: #fff;
      padding: 1rem;
      text-align: center;
      margin-top: 2rem;
    }
  </style>
</head>
<body>
  <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      $action = $_POST['action'];
      if ($action === 'add_to_cart') {
        echo '<script>alert("Product added to cart!");</script>';
      }
    }
  ?>

  <header>
    <nav>
      <a href="#">BabyCubs</a>
      <div class="dropdown">
        <button>Baby Girl</button>
        <div class="dropdown-content">
          <a href="#">Pajamas</a>
          <a href="#">Outfit Sets</a>
          <a href="#">Bodysuits</a>
          <a href="#">Tops</a>
          <a href="#">Bottoms</a>
          <a href="#">Dresses</a>
          <a href="#">Rompers & Jumpsuits</a>
          <a href="#">Jackets & Coats</a>
          <a href="#">Shoes</a>
        </div>
      </div>
      <div>
        <input type="text" placeholder="Search products" id="search-input">
        <button id="search-btn">Search</button>
      </div>
      <div class="right-nav">
        <!-- <a href="#">Free 1 Hour Pickup</a> -->
        <div class="user-actions">
          <button>Account</button>
          <button>Cart</button>
        </div>
      </div>
    </nav>
  </header>

  <main>
    <section class="banner">
      <h1>New Born Collection</h1>
      <p>Exclusive items for your little one</p>
      <button class="filter-btn">All Filters</button>
    </section>

    <section class="products">
      <div class="product-card">
        <img src="./images/pic37.jpg" alt="Baby Zip-Up Sherpa Cardigan">
        <h3>Baby Zip-Up Sherpa Cardigan</h3>
        <p>560.00 <span style="color: #0077b6;">(50% off)</span></p>
        <form method="POST">
          <input type="hidden" name="action" value="add_to_cart">
          <button type="submit" class="add-to-cart">Add to Cart</button>
        </form>
      </div>

      <div class="product-card">
        <img src="./images/pic33.jpg" alt="Baby Koala Hooded Terry Robe">
        <h3>Baby Koala Hooded Terry Robe</h3>
        <p>725.00 <span style="color: #0077b6;">(40% off)</span></p>
        <form method="POST">
          <input type="hidden" name="action" value="add_to_cart">
          <button type="submit" class="add-to-cart">Add to Cart</button>
        </form>
      </div>

      <div class="product-card">
        <img src="./images/pic34.jpg" alt="Baby 3-Piece Valentine's Day Set">
        <h3>Baby 3-Piece Valentine's Day Set</h3>
        <p>3115.00 <span style="color: #0077b6;">(60% off)</span></p>
        <form method="POST">
          <input type="hidden" name="action" value="add_to_cart">
          <button type="submit" class="add-to-cart">Add to Cart</button>
        </form>
      </div>

      <div class="product-card">
        <img src="./images/pic35.jpg" alt="Baby Heart Print Jumper Set">
        <h3>Heart Print Jumper Set</h3>
        <p>917.00 <span style="color: #0077b6;">(50% off)</span></p>
        <form method="POST">
          <input type="hidden" name="action" value="add_to_cart">
          <button type="submit" class="add-to-cart">Add to Cart</button>
        </form>
      </div>

      <div class="product-card">
        <img src="./images/pic36.jpg" alt="Autumn Girl Clothing Set Heart Printed ">
        <h3>Autumn Girl Clothing Set Heart Printed </h3>
        <p>$17.00 <span style="color: #0077b6;">(50% off)</span></p>
        <form method="POST">
          <input type="hidden" name="action" value="add_to_cart">
          <button type="submit" class="add-to-cart">Add to Cart</button>
        </form>
      </div>
    </section>

    <section class="products">
      <div class="product-card">
        <img src="./images/pic30.jpg" alt="Carter's Baby Zip-Up Sherpa Cardigan">
        <h3>Carter's Baby Zip-Up Sherpa Cardigan</h3>
        <p>$20.00 <span style="color: #0077b6;">(50% off)</span></p>
        <form method="POST">
          <input type="hidden" name="action" value="add_to_cart">
          <button type="submit" class="add-to-cart">Add to Cart</button>
        </form>
      </div>

      <div class="product-card">
        <img src="./images/pic25.jpg" alt="Carter's Baby Faux Fur Hooded Jacket">
        <h3>Carter's Baby Faux Fur Hooded Jacket</h3>
        <p>$25.00 <span style="color: #0077b6;">(40% off)</span></p>
        <form method="POST">
          <input type="hidden" name="action" value="add_to_cart">
          <button type="submit" class="add-to-cart">Add to Cart</button>
        </form>
      </div>

      <div class="product-card">
        <img src="./images/pic3.jpg" alt="Carter's Baby 3-Piece Valentine's Day Set">
        <h3>Carter's Baby 3-Piece Valentine's Day Set</h3>
        <p>$15.00 <span style="color: #0077b6;">(60% off)</span></p>
        <form method="POST">
          <input type="hidden" name="action" value="add_to_cart">
          <button type="submit" class="add-to-cart">Add to Cart</button>
        </form>
      </div>

      <div class="product-card">
        <img src="./images/pic4.jpg" alt="Carter's Baby Heart Print Jumper Set">
        <h3>Carter's Baby Heart Print Jumper Set</h3>
        <p>$17.00 <span style="color: #0077b6;">(50% off)</span></p>
        <form method="POST">
          <input type="hidden" name="action" value="add_to_cart">
          <button type="submit" class="add-to-cart">Add to Cart</button>
        </form>
      </div>
      
      <div class="product-card">
        <img src="./images/pic4.jpg" alt="Carter's Baby Heart Print Jumper Set">
        <h3>Carter's Baby Heart Print Jumper Set</h3>
        <p>$17.00 <span style="color: #0077b6;">(50% off)</span></p>
        <form method="POST">
          <input type="hidden" name="action" value="add_to_cart">
          <button type="submit" class="add-to-cart">Add to Cart</button>
        </form>
      </div>
    </section>


    <section class="products">
      <div class="product-card">
        <img src="./images/pic30.jpg" alt="Carter's Baby Zip-Up Sherpa Cardigan">
        <h3>Carter's Baby Zip-Up Sherpa Cardigan</h3>
        <p>$20.00 <span style="color: #0077b6;">(50% off)</span></p>
        <form method="POST">
          <input type="hidden" name="action" value="add_to_cart">
          <button type="submit" class="add-to-cart">Add to Cart</button>
        </form>
      </div>

      <div class="product-card">
        <img src="./images/pic25.jpg" alt="Carter's Baby Faux Fur Hooded Jacket">
        <h3>Carter's Baby Faux Fur Hooded Jacket</h3>
        <p>$25.00 <span style="color: #0077b6;">(40% off)</span></p>
        <form method="POST">
          <input type="hidden" name="action" value="add_to_cart">
          <button type="submit" class="add-to-cart">Add to Cart</button>
        </form>
      </div>

      <div class="product-card">
        <img src="./images/pic3.jpg" alt="Carter's Baby 3-Piece Valentine's Day Set">
        <h3>Carter's Baby 3-Piece Valentine's Day Set</h3>
        <p>$15.00 <span style="color: #0077b6;">(60% off)</span></p>
        <form method="POST">
          <input type="hidden" name="action" value="add_to_cart">
          <button type="submit" class="add-to-cart">Add to Cart</button>
        </form>
      </div>

      <div class="product-card">
        <img src="./images/pic4.jpg" alt="Carter's Baby Heart Print Jumper Set">
        <h3>Carter's Baby Heart Print Jumper Set</h3>
        <p>$17.00 <span style="color: #0077b6;">(50% off)</span></p>
        <form method="POST">
          <input type="hidden" name="action" value="add_to_cart">
          <button type="submit" class="add-to-cart">Add to Cart</button>
        </form>
      </div>
      
      <div class="product-card">
        <img src="./images/pic4.jpg" alt="Carter's Baby Heart Print Jumper Set">
        <h3>Carter's Baby Heart Print Jumper Set</h3>
        <p>$17.00 <span style="color: #0077b6;">(50% off)</span></p>
        <form method="POST">
          <input type="hidden" name="action" value="add_to_cart">
          <button type="submit" class="add-to-cart">Add to Cart</button>
        </form>
      </div>
    </section>

    <section class="products">
      <div class="product-card">
        <img src="./images/pic30.jpg" alt="Carter's Baby Zip-Up Sherpa Cardigan">
        <h3>Carter's Baby Zip-Up Sherpa Cardigan</h3>
        <p>$20.00 <span style="color: #0077b6;">(50% off)</span></p>
        <form method="POST">
          <input type="hidden" name="action" value="add_to_cart">
          <button type="submit" class="add-to-cart">Add to Cart</button>
        </form>
      </div>

      <div class="product-card">
        <img src="./images/pic25.jpg" alt="Carter's Baby Faux Fur Hooded Jacket">
        <h3>Carter's Baby Faux Fur Hooded Jacket</h3>
        <p>$25.00 <span style="color: #0077b6;">(40% off)</span></p>
        <form method="POST">
          <input type="hidden" name="action" value="add_to_cart">
          <button type="submit" class="add-to-cart">Add to Cart</button>
        </form>
      </div>

      <div class="product-card">
        <img src="./images/pic3.jpg" alt="Carter's Baby 3-Piece Valentine's Day Set">
        <h3>Carter's Baby 3-Piece Valentine's Day Set</h3>
        <p>$15.00 <span style="color: #0077b6;">(60% off)</span></p>
        <form method="POST">
          <input type="hidden" name="action" value="add_to_cart">
          <button type="submit" class="add-to-cart">Add to Cart</button>
        </form>
      </div>

      <div class="product-card">
        <img src="./images/pic4.jpg" alt="Carter's Baby Heart Print Jumper Set">
        <h3>Carter's Baby Heart Print Jumper Set</h3>
        <p>$17.00 <span style="color: #0077b6;">(50% off)</span></p>
        <form method="POST">
          <input type="hidden" name="action" value="add_to_cart">
          <button type="submit" class="add-to-cart">Add to Cart</button>
        </form>
      </div>
      
      <div class="product-card">
        <img src="./images/pic4.jpg" alt="Carter's Baby Heart Print Jumper Set">
        <h3>Carter's Baby Heart Print Jumper Set</h3>
        <p>$17.00 <span style="color: #0077b6;">(50% off)</span></p>
        <form method="POST">
          <input type="hidden" name="action" value="add_to_cart">
          <button type="submit" class="add-to-cart">Add to Cart</button>
        </form>
      </div>
    </section>
  </main>

  <footer>
    <p>&copy; 2025 Carter's New Born. All rights reserved.</p>
  </footer>

  <script>
    const searchInput = document.getElementById('search-input');
    const searchBtn = document.getElementById('search-btn');
    const filterBtn = document.querySelector('.filter-btn');

    searchBtn.addEventListener('click', () => {
      const searchTerm = searchInput.value.trim();
      if (searchTerm) {
        alert(`Searching for: ${searchTerm}`);
      } else {
        alert('Please enter a search term');
      }
    });

    filterBtn.addEventListener('click', () => {
      alert('Filter options coming soon!');
    });
  </script>
</body>
</html>