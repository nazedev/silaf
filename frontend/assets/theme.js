function getContrastYIQ(hexcolor){
    hexcolor = hexcolor.replace("#", "");
    if(hexcolor.length === 3) hexcolor = hexcolor.split('').map(x => x+x).join('');
    var r = parseInt(hexcolor.substr(0,2),16);
    var g = parseInt(hexcolor.substr(2,2),16);
    var b = parseInt(hexcolor.substr(4,2),16);
    var yiq = ((r*299)+(g*587)+(b*114))/1000;
    return (yiq >= 128) ? 'black' : 'white';
}
const savedColor = localStorage.getItem('silaf-primary-color') || '#ff00ff';
document.documentElement.style.setProperty('--color-primary', savedColor);
document.documentElement.style.setProperty('--color-text-on-primary', getContrastYIQ(savedColor));
if (localStorage.getItem('theme') === 'dark' || (!localStorage.getItem('theme') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
    document.documentElement.classList.add('dark');
}
document.addEventListener('DOMContentLoaded', () => {
    if (!document.getElementById('disable-auto-theme')) {
        injectColorPicker(savedColor);
    } else {
        document.querySelectorAll('.theme-color-input').forEach(input => {
            input.value = savedColor;
        });
    }
});
window.updateColor = function(newColor) {
    document.documentElement.style.setProperty('--color-primary', newColor);
    document.documentElement.style.setProperty('--color-text-on-primary', getContrastYIQ(newColor));
    localStorage.setItem('silaf-primary-color', newColor);
    document.querySelectorAll('.theme-color-input').forEach(input => {
        input.value = newColor;
    });
};
function injectColorPicker(initialColor) {
    const container = document.createElement('div');
    container.className = 'fixed top-4 right-4 z-[9999] flex items-center gap-2';
    const themeBtn = document.createElement('button');
    themeBtn.className = 'p-2 brutal-border brutal-shadow bg-white dark:bg-gray-800 text-black dark:text-white font-bold cursor-pointer';
    themeBtn.innerHTML = '🌓';
    themeBtn.onclick = toggleTheme;
    const pickerWrapper = document.createElement('div');
    pickerWrapper.className = 'relative w-10 h-10 rounded-full cursor-pointer color-wheel';
    pickerWrapper.title = 'Ubah Warna Tema';
    const colorInput = document.createElement('input');
    colorInput.type = 'color';
    colorInput.value = initialColor;
    colorInput.className = 'absolute inset-0 w-full h-full opacity-0 cursor-pointer';
    colorInput.addEventListener('input', (e) => {
        const newColor = e.target.value;
        document.documentElement.style.setProperty('--color-primary', newColor);
        document.documentElement.style.setProperty('--color-text-on-primary', getContrastYIQ(newColor));
        localStorage.setItem('silaf-primary-color', newColor);
    });
    pickerWrapper.appendChild(colorInput);
    container.appendChild(themeBtn);
    container.appendChild(pickerWrapper);
    document.body.appendChild(container);
}
function toggleTheme() {
    const isDark = document.documentElement.classList.toggle('dark');
    localStorage.setItem('theme', isDark ? 'dark' : 'light');
}
window.tailwind.config = {
    darkMode: 'class',
    theme: {
        extend: {
            colors: {
                primary: 'var(--color-primary)',
            },
            fontFamily: {
                sans: ['Space Grotesk', 'sans-serif'],
            }
        }
    }
}
