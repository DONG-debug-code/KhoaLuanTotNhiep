/** @type {import('tailwindcss').Config} */
export default {
  content: ["./index.html", "./src/**/*.{js,jsx}"],
  theme: {
    extend: {
      fontSize: {
        xs: ['0.8125rem', { lineHeight: '1.15rem' }],
        sm: ['0.9375rem', { lineHeight: '1.35rem' }],
        base: ['1.0625rem', { lineHeight: '1.6rem' }],
      },
      fontFamily: {
        sans: ['Barlow', 'system-ui', 'sans-serif'],
        mono: ['Barlow', 'system-ui', 'sans-serif'],
      },
    },
    plugins: [],
  }
}
