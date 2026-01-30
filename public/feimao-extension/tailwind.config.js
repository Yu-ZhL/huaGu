/** @type {import('tailwindcss').Config} */
export default {
  important: true,
  content: [
    "./index.html",
    "./src/**/*.{vue,js,ts,jsx,tsx}",
  ],
  theme: {
    extend: {},
  },
  corePlugins: {
    preflight: false, // 禁用基础重置，防止弄乱页面原生样式
  },
  plugins: [],
}

