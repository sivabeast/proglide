<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Loading PROTECTORS...</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700;800&display=swap" rel="stylesheet">
<style>
/* ===============================
   RESET & BASE STYLES
================================ */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

:root {
    --primary-color: #6366f1;
    --primary-light: #818cf8;
    --primary-dark: #4f46e5;
    --bg-gradient-start: #020617;
    --bg-gradient-end: #000000;
    --text-color: #f8fafc;
    --shadow-color: rgba(99, 102, 241, 0.3);
    --accent-color: #10b981;
}

/* ===============================
   BODY
================================ */
body {
    background: radial-gradient(ellipse at top, var(--bg-gradient-start), var(--bg-gradient-end));
    height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    font-family: 'Poppins', Arial, sans-serif;
    overflow: hidden;
    position: relative;
    margin-top: 1px;
}

/* ===============================
   BACKGROUND EFFECTS
================================ */
body::before {
    content: '';
    position: absolute;
    width: 100%;
    height: 100%;
    background-image: 
        radial-gradient(circle at 20% 30%, rgba(99, 102, 241, 0.05) 0%, transparent 20%),
        radial-gradient(circle at 80% 70%, rgba(16, 185, 129, 0.05) 0%, transparent 20%),
        radial-gradient(circle at 40% 80%, rgba(99, 102, 241, 0.03) 0%, transparent 15%);
    z-index: 0;
}

/* Floating particles */
.particles {
    position: absolute;
    width: 100%;
    height: 100%;
    z-index: 1;
    overflow: hidden;
}

.particle {
    position: absolute;
    border-radius: 50%;
    background: rgba(99, 102, 241, 0.15);
    animation: float 15s infinite linear;
}

/* ===============================
   SPLASH CONTAINER
================================ */
.splash {
    text-align: center;
    z-index: 10;
    position: relative;
    max-width: 90%;
    animation: containerAppear 1.5s ease forwards;
    margin-top: -100px;
}

/* ===============================
   LOGO STYLES
================================ */
.logo-container {
    margin-bottom: 40px;
    position: relative;
}

.logo {
    font-size: 3.5rem;
    font-weight: 800;
    letter-spacing: 6px;
    color: transparent;
    background: linear-gradient(135deg, var(--primary-color), var(--primary-light), var(--accent-color));
    background-clip: text;
    -webkit-background-clip: text;
    margin-bottom: 16px;
    position: relative;
    animation: logoReveal 1.2s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
    opacity: 0;
    text-transform: uppercase;
    filter: drop-shadow(0 0 10px var(--shadow-color));
}

.logo-subtitle {
    font-size: 0.9rem;
    font-weight: 300;
    letter-spacing: 3px;
    color: rgba(248, 250, 252, 0.7);
    text-transform: uppercase;
    animation: fadeInUp 1s ease 0.8s forwards;
    opacity: 0;
}

/* Logo underline effect */
.logo-underline {
    position: absolute;
    bottom: -12px;
    left: 50%;
    transform: translateX(-50%) scaleX(0);
    width: 120px;
    height: 3px;
    background: linear-gradient(90deg, transparent, var(--primary-color), var(--accent-color), transparent);
    border-radius: 3px;
    animation: drawLine 0.8s ease 0.5s forwards;
}

/* ===============================
   LOADER STYLES
================================ */
.loader-container {
    position: relative;
    width: 100px;
    height: 100px;
    margin: 0 auto;
}

.loader {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    position: relative;
    animation: rotate 1.5s linear infinite;
}

.loader::before {
    content: "";
    position: absolute;
    inset: 8px;
    border-radius: 50%;
    background: rgba(99, 102, 241, 0.05);
    z-index: 2;
}

.loader-circle {
    position: absolute;
    width: 100%;
    height: 100%;
    border-radius: 50%;
    border: 4px solid transparent;
    border-top-color: var(--primary-color);
    border-right-color: var(--primary-light);
    border-bottom-color: var(--accent-color);
    filter: drop-shadow(0 0 8px var(--shadow-color));
}

.loader-circle:nth-child(2) {
    border: 4px solid transparent;
    border-top-color: rgba(99, 102, 241, 0.3);
    border-right-color: rgba(99, 102, 241, 0.2);
    border-bottom-color: rgba(16, 185, 129, 0.2);
    inset: -6px;
    animation: rotateReverse 2s linear infinite;
}

.loader-dot {
    position: absolute;
    width: 16px;
    height: 16px;
    background: var(--primary-color);
    border-radius: 50%;
    top: 8px;
    left: 50%;
    transform: translateX(-50%);
    z-index: 3;
    box-shadow: 0 0 10px var(--primary-color);
}

/* Loading text */
.loading-text {
    margin-top: 24px;
    font-size: 0.9rem;
    font-weight: 300;
    color: rgba(248, 250, 252, 0.7);
    letter-spacing: 2px;
    text-transform: uppercase;
    animation: pulse 2s infinite;
}

.loading-dots {
    display: inline-block;
    width: 20px;
    text-align: left;
}

/* ===============================
   ANIMATIONS
================================ */
@keyframes containerAppear {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes logoReveal {
    0% {
        opacity: 0;
        transform: translateY(30px) scale(0.8);
        letter-spacing: 20px;
    }
    60% {
        opacity: 1;
        transform: translateY(-10px) scale(1.05);
    }
    100% {
        opacity: 1;
        transform: translateY(0) scale(1);
        letter-spacing: 6px;
    }
}

@keyframes drawLine {
    0% {
        transform: translateX(-50%) scaleX(0);
    }
    100% {
        transform: translateX(-50%) scaleX(1);
    }
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes rotate {
    0% {
        transform: rotate(0deg);
    }
    100% {
        transform: rotate(360deg);
    }
}

@keyframes rotateReverse {
    0% {
        transform: rotate(0deg);
    }
    100% {
        transform: rotate(-360deg);
    }
}

@keyframes float {
    0%, 100% {
        transform: translateY(0) translateX(0);
    }
    25% {
        transform: translateY(-20px) translateX(10px);
    }
    50% {
        transform: translateY(-10px) translateX(20px);
    }
    75% {
        transform: translateY(10px) translateX(-10px);
    }
}

@keyframes pulse {
    0%, 100% {
        opacity: 0.7;
    }
    50% {
        opacity: 1;
    }
}

/* ===============================
   PROGRESS BAR (OPTIONAL)
================================ */
.progress-container {
    width: 200px;
    height: 4px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 2px;
    margin: 30px auto 0;
    overflow: hidden;
}

.progress-bar {
    height: 100%;
    width: 0%;
    background: linear-gradient(90deg, var(--primary-color), var(--accent-color));
    border-radius: 2px;
    animation: progressFill 2.5s ease-in-out forwards;
}

/* ===============================
   MOBILE OPTIMIZATION
================================ */
@media (max-width: 768px) {
    .logo {
        font-size: 2.5rem;
        letter-spacing: 4px;
    }
    
    .logo-subtitle {
        font-size: 0.8rem;
        letter-spacing: 2px;
    }
    
    .loader-container {
        width: 80px;
        height: 80px;
    }
    
    .progress-container {
        width: 180px;
    }
}

@media (max-width: 480px) {
    .logo {
        font-size: 2rem;
        letter-spacing: 3px;
    }
    
    .logo-subtitle {
        font-size: 0.7rem;
    }
    
    .loader-container {
        width: 70px;
        height: 70px;
    }
    
    .loader-dot {
        width: 14px;
        height: 14px;
    }
    
    .loading-text {
        font-size: 0.8rem;
    }
}

/* ===============================
   REDUCED MOTION PREFERENCE
================================ */
@media (prefers-reduced-motion: reduce) {
    .logo, .loader, .loader-circle, .particle, .progress-bar {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
    }
    
    .logo {
        opacity: 1;
        transform: none;
        letter-spacing: normal;
    }
}
</style>
</head>
<body>

<!-- Background particles -->
<div class="particles" id="particles"></div>

<!-- Main splash container -->
<div class="splash">
    <div class="logo-container">
        <div class="logo">PROTECTORS</div>
        
        
    </div>
    
    
    
    <div class="loading-text">
        Loading<span class="loading-dots">...</span>
    </div>
    
    <!-- Optional progress bar -->
    <div class="progress-container">
        <div class="progress-bar"></div>
    </div>
</div>

<script>
// Create background particles
function createParticles() {
    const particlesContainer = document.getElementById('particles');
    const particleCount = Math.min(50, Math.floor(window.innerWidth / 20));
    
    for (let i = 0; i < particleCount; i++) {
        const particle = document.createElement('div');
        particle.classList.add('particle');
        
        // Random size
        const size = Math.random() * 4 + 1;
        particle.style.width = `${size}px`;
        particle.style.height = `${size}px`;
        
        // Random position
        particle.style.left = `${Math.random() * 100}%`;
        particle.style.top = `${Math.random() * 100}%`;
        
        // Random animation delay
        particle.style.animationDelay = `${Math.random() * 15}s`;
        
        // Random opacity
        particle.style.opacity = Math.random() * 0.5 + 0.1;
        
        particlesContainer.appendChild(particle);
    }
}

// Animate loading dots
function animateDots() {
    const dots = document.querySelector('.loading-dots');
    let dotCount = 0;
    
    setInterval(() => {
        dotCount = (dotCount + 1) % 4;
        dots.textContent = '.'.repeat(dotCount);
    }, 500);
}

// Update progress bar
function updateProgressBar() {
    const progressBar = document.querySelector('.progress-bar');
    let width = 0;
    
    const interval = setInterval(() => {
        if (width >= 100) {
            clearInterval(interval);
        } else {
            width += Math.random() * 15 + 5;
            if (width > 100) width = 100;
            progressBar.style.width = `${width}%`;
        }
    }, 250);
}

// Initialize everything when page loads
document.addEventListener('DOMContentLoaded', () => {
    createParticles();
    animateDots();
    updateProgressBar();
    
    // Redirect after 2.5 seconds
    setTimeout(() => {
        // Add fade out animation before redirecting
        document.body.style.opacity = '0';
        document.body.style.transition = 'opacity 0.5s ease';
        
        setTimeout(() => {
            window.location.href = "index.php";
        }, 500);
    }, 2500);
});

// Handle page visibility (if user switches tabs)
document.addEventListener('visibilitychange', () => {
    if (document.hidden) {
        console.log('Splash screen paused');
    } else {
        console.log('Splash screen resumed');
    }
});
</script>
</body>
</html>