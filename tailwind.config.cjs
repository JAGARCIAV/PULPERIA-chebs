/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./index.php",
    "./index.html",
    "./vistas/**/*.{php,html,js}",
    "./controladores/**/*.php",
    "./modelos/**/*.php",
    "./public/**/*.{js,php,html}"
  ],
  theme: {
    extend: {
      colors: {
        chebs: {
          green: '#4E7A2B',
          greenDark: '#3b631d',
          black: '#111111',
          soft: '#c7d8b9',
          line: '#E5E7EB',
        }
      },
      boxShadow: {
        soft: '0 10px 30px rgba(0,0,0,.08)',
      }
    },
  },
  plugins: [],
};