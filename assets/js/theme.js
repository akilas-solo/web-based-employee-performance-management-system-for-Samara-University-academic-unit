/**
 * Samara University Academic Performance Evaluation System
 * Theme JavaScript
 */

document.addEventListener('DOMContentLoaded', function() {
    // Get the current theme class from body
    const body = document.body;
    let currentTheme = '';
    
    if (body.classList.contains('admin-theme')) {
        currentTheme = 'admin-theme';
    } else if (body.classList.contains('head-theme')) {
        currentTheme = 'head-theme';
    } else if (body.classList.contains('dean-theme')) {
        currentTheme = 'dean-theme';
    } else if (body.classList.contains('college-theme')) {
        currentTheme = 'college-theme';
    } else if (body.classList.contains('hrm-theme')) {
        currentTheme = 'hrm-theme';
    }
    
    // If no theme is set, default to admin-theme
    if (!currentTheme) {
        body.classList.add('admin-theme');
        currentTheme = 'admin-theme';
    }
    
    // Apply theme to buttons
    applyThemeToButtons();
    
    // Apply theme to text elements
    applyThemeToText();
    
    // Apply theme to borders
    applyThemeToBorders();
    
    // Apply theme to backgrounds
    applyThemeToBackgrounds();
    
    // Function to apply theme to buttons
    function applyThemeToButtons() {
        // Button class mapping for each theme
        const buttonMap = {
            'admin-theme': {
                'btn-primary': 'btn-theme',
                'btn-outline-primary': 'btn-outline-theme'
            },
            'head-theme': {
                'btn-primary': 'btn-theme',
                'btn-outline-primary': 'btn-outline-theme'
            },
            'dean-theme': {
                'btn-primary': 'btn-theme',
                'btn-outline-primary': 'btn-outline-theme'
            },
            'college-theme': {
                'btn-primary': 'btn-theme',
                'btn-outline-primary': 'btn-outline-theme'
            },
            'hrm-theme': {
                'btn-primary': 'btn-theme',
                'btn-outline-primary': 'btn-outline-theme'
            }
        };
        
        // Get the button map for the current theme
        const themeButtonMap = buttonMap[currentTheme];
        
        if (themeButtonMap) {
            // Loop through each button class mapping
            for (const [oldClass, newClass] of Object.entries(themeButtonMap)) {
                // Find all elements with the old class
                const elements = document.querySelectorAll('.' + oldClass);
                
                // Add the new class to each element
                elements.forEach(function(element) {
                    element.classList.add(newClass);
                });
            }
        }
    }
    
    // Function to apply theme to text elements
    function applyThemeToText() {
        // Text class mapping for each theme
        const textMap = {
            'admin-theme': {
                'text-primary': 'text-theme'
            },
            'head-theme': {
                'text-primary': 'text-theme'
            },
            'dean-theme': {
                'text-primary': 'text-theme'
            },
            'college-theme': {
                'text-primary': 'text-theme'
            },
            'hrm-theme': {
                'text-primary': 'text-theme'
            }
        };
        
        // Get the text map for the current theme
        const themeTextMap = textMap[currentTheme];
        
        if (themeTextMap) {
            // Loop through each text class mapping
            for (const [oldClass, newClass] of Object.entries(themeTextMap)) {
                // Find all elements with the old class
                const elements = document.querySelectorAll('.' + oldClass);
                
                // Add the new class to each element
                elements.forEach(function(element) {
                    element.classList.add(newClass);
                });
            }
        }
    }
    
    // Function to apply theme to borders
    function applyThemeToBorders() {
        // Border class mapping for each theme
        const borderMap = {
            'admin-theme': {
                'border-primary': 'border-theme'
            },
            'head-theme': {
                'border-primary': 'border-theme'
            },
            'dean-theme': {
                'border-primary': 'border-theme'
            },
            'college-theme': {
                'border-primary': 'border-theme'
            },
            'hrm-theme': {
                'border-primary': 'border-theme'
            }
        };
        
        // Get the border map for the current theme
        const themeBorderMap = borderMap[currentTheme];
        
        if (themeBorderMap) {
            // Loop through each border class mapping
            for (const [oldClass, newClass] of Object.entries(themeBorderMap)) {
                // Find all elements with the old class
                const elements = document.querySelectorAll('.' + oldClass);
                
                // Add the new class to each element
                elements.forEach(function(element) {
                    element.classList.add(newClass);
                });
            }
        }
    }
    
    // Function to apply theme to backgrounds
    function applyThemeToBackgrounds() {
        // Background class mapping for each theme
        const bgMap = {
            'admin-theme': {
                'bg-primary': 'bg-theme'
            },
            'head-theme': {
                'bg-primary': 'bg-theme'
            },
            'dean-theme': {
                'bg-primary': 'bg-theme'
            },
            'college-theme': {
                'bg-primary': 'bg-theme'
            },
            'hrm-theme': {
                'bg-primary': 'bg-theme'
            }
        };
        
        // Get the background map for the current theme
        const themeBgMap = bgMap[currentTheme];
        
        if (themeBgMap) {
            // Loop through each background class mapping
            for (const [oldClass, newClass] of Object.entries(themeBgMap)) {
                // Find all elements with the old class
                const elements = document.querySelectorAll('.' + oldClass);
                
                // Add the new class to each element
                elements.forEach(function(element) {
                    element.classList.add(newClass);
                });
            }
        }
    }
});
