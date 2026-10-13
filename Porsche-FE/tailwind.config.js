/** @type {import('tailwindcss').Config} */
export default {
  content: ["./index.html", "./src/**/*.{js,jsx}"],
  theme: {
    extend: {
      fontFamily: {
        sans: ['"General Sans"', 'Inter', 'sans-serif'],
      },
    },
    plugins: [],
  }
}
