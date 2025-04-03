// Get the button and body elements
const themeToggleBtn = document.getElementById('theme-toggle');
const body = document.body;

// Check if dark mode is already set in localStorage
if (localStorage.getItem('theme') === 'dark') {
    body.classList.add('dark-theme');
    themeToggleBtn.innerText = 'Switch to Light Mode';
} else {
    themeToggleBtn.innerText = 'Switch to Dark Mode';
}

// Add event listener to the button
themeToggleBtn.addEventListener('click', () => {
    // Toggle dark mode class on body
    body.classList.toggle('dark-theme');
    
    // Update the button text based on the theme
    if (body.classList.contains('dark-theme')) {
        themeToggleBtn.innerText = 'Switch to Light Mode';
        localStorage.setItem('theme', 'dark');  // Save dark mode setting
    } else {
        themeToggleBtn.innerText = 'Switch to Dark Mode';
        localStorage.setItem('theme', 'light');  // Save light mode setting
    }
});
