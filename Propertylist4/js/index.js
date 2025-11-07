document.addEventListener('DOMContentLoaded', () => {
    const hamburgerMenu = document.querySelector('.hamburger-menu');
    const navList = document.querySelector('.nav-list');

    // Toggle mobile menu
    hamburgerMenu.addEventListener('click', () => {
        navList.classList.toggle('active');
        
        // Animate hamburger icon
        hamburgerMenu.classList.toggle('active');
    });

    // Close menu when clicking outside
    document.addEventListener('click', (event) => {
        if (!hamburgerMenu.contains(event.target) && !navList.contains(event.target)) {
            navList.classList.remove('active');
            hamburgerMenu.classList.remove('active');
        }
    });

    // Optional: Smooth scrolling for navigation links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            document.querySelector(this.getAttribute('href')).scrollIntoView({
                behavior: 'smooth'
            });
        });
    });
});

// Wait for the DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    // Get the modal and button elements
    const modal = document.getElementById('authModal');
    const findHouseButtons = document.querySelectorAll('button');
    const closeButton = document.querySelector('.close-button');
    const tabButtons = document.querySelectorAll('.tab-button');
    const tabContents = document.querySelectorAll('.tab-content');
    
    // Add click event to all "Find a house" or "Get Started" buttons
    findHouseButtons.forEach(button => {
        if (button.textContent.includes('Find a house') || button.textContent.includes('Get Started')) {
            button.addEventListener('click', function() {
                modal.style.display = 'block';
                document.body.style.overflow = 'hidden'; // Prevent scrolling when modal is open
            });
        }
    });
    
    // Close the modal when clicking the close button
    closeButton.addEventListener('click', function() {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto'; // Re-enable scrolling
    });
    
    // Close the modal when clicking outside of it
    window.addEventListener('click', function(event) {
        if (event.target === modal) {
            modal.style.display = 'none';
            document.body.style.overflow = 'auto'; // Re-enable scrolling
        }
    });
    
    // Handle tab switching
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Remove active class from all buttons
            tabButtons.forEach(btn => btn.classList.remove('active'));
            
            // Add active class to clicked button
            this.classList.add('active');
            
            // Get the tab to activate
            const tabToActivate = this.getAttribute('data-tab');
            
            // Hide all tab contents
            tabContents.forEach(content => content.classList.remove('active'));
            
            // Show the selected tab content
            document.getElementById(tabToActivate).classList.add('active');
        });
    });
    
    // Form submission handling (prevent default for demo)
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            alert('Form submitted! This would normally send data to the server.');
            // In a real application, you would send the form data to your server here
        });
    });
});

