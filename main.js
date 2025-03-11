import { initializeApp } from "https://www.gstatic.com/firebasejs/11.4.0/firebase-app.js";
import { getAuth, GoogleAuthProvider, signInWithPopup, signOut } from "https://www.gstatic.com/firebasejs/11.4.0/firebase-auth.js";

const firebaseConfig = {
  apiKey: "AIzaSyAiyisE46jFew1s5-dog5zl3d_srOTQGrk",
  authDomain: "baby-74b6b.firebaseapp.com",
  projectId: "baby-74b6b",
  storageBucket: "baby-74b6b.firebasestorage.app",
  messagingSenderId: "611501822780",
  appId: "1:611501822780:web:bf501ff468b22953eb4439"
};

// Initialize Firebase
const app = initializeApp(firebaseConfig);
const auth = getAuth(app);
auth.languageCode = 'en';
const provider = new GoogleAuthProvider();

// Force account selection by adding the 'prompt' parameter
provider.setCustomParameters({
  prompt: 'select_account'
});

const googleLogin = document.getElementById("google-login-btn");
if (googleLogin) {
  googleLogin.addEventListener("click", function() {
    // First sign out from any previous session
    signOut(auth).then(() => {
      // Then proceed with new sign in
      signInWithPopup(auth, provider)
        .then((result) => {
          const credential = GoogleAuthProvider.credentialFromResult(result);
          const user = result.user;
          console.log(user);
          
          // Send the user data to your server
          return fetch('process_google_login.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
            },
            body: JSON.stringify({
              email: user.email,
              name: user.displayName,
              uid: user.uid,
              photoURL: user.photoURL
            })
          });
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            window.location.href = data.redirect;
          } else {
            alert("Login failed: " + data.message);
          }
        })
        .catch((error) => {
          console.error("Error during Google sign-in:", error);
          if (error.code !== 'auth/cancelled-popup-request' && 
              error.code !== 'auth/popup-closed-by-user') {
            alert("Login failed: " + error.message);
          }
        });
    }).catch((error) => {
      console.error("Error signing out:", error);
    });
  });
}

// Add this code to your logout page/function
function handleLogout() {
  // Sign out from Firebase
  signOut(auth).then(() => {
    console.log("Firebase logout successful");
    // Continue with your regular PHP logout
    window.location.href = "logout.php"; // Your existing logout script
  }).catch((error) => {
    console.error("Firebase logout error:", error);
    // Continue with regular logout even if Firebase logout fails
    window.location.href = "logout.php";
  });
}

// If there's a logout button on the current page, attach the handler
const logoutBtn = document.getElementById("logout-btn");
if (logoutBtn) {
  logoutBtn.addEventListener("click", handleLogout);
}