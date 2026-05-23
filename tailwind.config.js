const withAlpha = (variable) => `rgb(var(${variable}) / <alpha-value>)`;

module.exports = {
  content: [
    './app/Views/**/*.php',
    './public/assets/js/**/*.js'
  ],
  theme: {
    extend: {
      colors: {
        paper: withAlpha('--paper-rgb'),
        'paper-strong': withAlpha('--paper-strong-rgb'),
        cream: withAlpha('--cream-rgb'),
        mint: {
          50: withAlpha('--mint-soft-rgb'),
          100: withAlpha('--mint-soft-rgb'),
          200: withAlpha('--mint-rgb'),
          500: withAlpha('--mint-rgb')
        },
        ink: withAlpha('--ink-rgb'),
        'ink-soft': withAlpha('--ink-soft-rgb'),
        leaf: {
          500: withAlpha('--mint-rgb'),
          600: 'rgb(66 190 176 / <alpha-value>)',
          700: 'rgb(42 116 110 / <alpha-value>)'
        },
        blush: {
          100: withAlpha('--peach-soft-rgb'),
          300: withAlpha('--rose-rgb'),
          500: withAlpha('--rose-rgb')
        },
        purple: {
          100: withAlpha('--purple-soft-rgb'),
          500: withAlpha('--purple-rgb')
        },
        peach: withAlpha('--peach-rgb'),
        rose: {
          500: withAlpha('--rose-rgb'),
          600: withAlpha('--rose-rgb'),
          700: 'rgb(186 73 106 / <alpha-value>)'
        },
        water: withAlpha('--sky-rgb'),
        sky: withAlpha('--sky-rgb'),
        gold: withAlpha('--gold-rgb')
      },
      boxShadow: {
        soft: 'var(--shadow-panel)',
        button: 'var(--shadow-card)'
      },
      fontFamily: {
        sans: ['Nunito', 'Chulabhorn Likit Text', 'ui-sans-serif', 'system-ui', 'sans-serif'],
        thai: ['Chulabhorn Likit Text', 'ui-sans-serif', 'system-ui', 'sans-serif'],
        latin: ['Nunito', 'ui-sans-serif', 'system-ui', 'sans-serif']
      }
    }
  },
  plugins: [
    require('@tailwindcss/forms')
  ]
};
