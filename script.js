// Load products dynamically
function loadProducts() {
  const products = [];
  for(let i=1; i<=20; i++) {
    products.push({ id: i, name: "Product " + i, price: (i*10), image: "https://via.placeholder.com/200" });
  }

  const productList = document.getElementById("product-list");
  if(productList) {
    products.forEach(p => {
      const card = document.createElement("div");
      card.className = "product-card";
      card.innerHTML = `
        <img src="${p.image}" alt="${p.name}">
        <h3>${p.name}</h3>
        <p>$${p.price}</p>
      `;
      productList.appendChild(card);
    });
  }
}

// Initialize products page
window.onload = function() {
  if(document.getElementById("product-list")) {
    loadProducts();
  }
};
let cart = [];

// Load products dynamically
function loadProducts() {
  const products = [];
  for(let i=1; i<=20; i++) {
    products.push({ id: i, name: "Product " + i, price: (i*10), image: "https://via.placeholder.com/200" });
  }

  const productList = document.getElementById("product-list");
  if(productList) {
    products.forEach(p => {
      const card = document.createElement("div");
      card.className = "product-card";
      card.innerHTML = `
        <img src="${p.image}" alt="${p.name}">
        <h3>${p.name}</h3>
        <p>$${p.price}</p>
        <button onclick="addToCart(${p.id}, '${p.name}', ${p.price})">Add to Cart</button>
      `;
      productList.appendChild(card);
    });
  }
}

// Add product to cart
function addToCart(id, name, price) {
  const item = cart.find(p => p.id === id);
  if(item) {
    item.quantity++;
  } else {
    cart.push({ id, name, price, quantity: 1 });
  }
  updateCartCount();
}

// Update cart count in header
function updateCartCount() {
  const countElement = document.getElementById("cart-count");
  if(countElement) {
    countElement.textContent = cart.reduce((sum, item) => sum + item.quantity, 0);
  }
}

// Show cart modal
function viewCart() {
  const modal = document.getElementById("cart-modal");
  const cartItems = document.getElementById("cart-items");
  cartItems.innerHTML = "";

  cart.forEach(item => {
    const li = document.createElement("li");
    li.className = "cart-item";
    li.innerHTML = `
      ${item.name} (x${item.quantity}) - $${item.price * item.quantity}
      <button onclick="removeFromCart(${item.id})">Remove</button>
    `;
    cartItems.appendChild(li);
  });

  const total = cart.reduce((sum, item) => sum + item.price * item.quantity, 0);
  const totalElement = document.createElement("p");
  totalElement.innerHTML = `<strong>Total: $${total}</strong>`;
  cartItems.appendChild(totalElement);

  modal.style.display = "block";
}

// Close cart modal
function closeCart() {
  document.getElementById("cart-modal").style.display = "none";
}

// Remove item from cart
function removeFromCart(id) {
  cart = cart.filter(item => item.id !== id);
  updateCartCount();
  viewCart();
}

// Checkout - renamed from 'fetch' to avoid conflict with native fetch API
function checkout() {
  if(cart.length === 0) {
    alert("Your cart is empty!");
    return;
  }
  alert("Checkout successful! (Connect this to PHP/MySQL backend)");
  cart = [];
  updateCartCount();
  closeCart();
}

// Initialize products page
window.onload = function() {
  if(document.getElementById("product-list")) {
    loadProducts();
  }
};


