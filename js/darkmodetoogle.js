// darkmodetogle.js
const themeToggleBtn = document.getElementById('theme-toggle');
const html = document.documentElement;

// Check if the user has a saved theme preference
const currentTheme = localStorage.getItem('theme');

// If a theme preference exists, apply it
if (currentTheme) {
    html.setAttribute('data-bs-theme', currentTheme);
    themeToggleBtn.textContent = currentTheme === 'dark' ? 'Switch to Light Mode' : 'Switch to Dark Mode';
} else {
    // If no theme preference exists, default to light mode
    html.setAttribute('data-bs-theme', 'light');
    themeToggleBtn.textContent = 'Switch to Dark Mode';
}

// Add event listener to toggle the theme on button click
themeToggleBtn.addEventListener('click', () => {
    const currentTheme = html.getAttribute('data-bs-theme');
    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';

    // Toggle the theme
    html.setAttribute('data-bs-theme', newTheme);

    // Store the new theme preference in localStorage
    localStorage.setItem('theme', newTheme);

    // Update button text
    themeToggleBtn.textContent = newTheme === 'dark' ? 'Switch to Light Mode' : 'Switch to Dark Mode';
});
