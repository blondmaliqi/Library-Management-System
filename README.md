# 📚 Library Management System

## 📖 About the Project

The Library Management System is a web application designed to simplify book browsing, borrowing, reading, and library account management. It provides users with an organized platform to explore books, manage their favorite lists, request book loans, and track late fees.

## ✨ Features

- 🔍 **Book Search and Filtering** — Search for books by title, author, or keywords and filter them by category.
- ⭐ **Favorite Books** — Add or remove books from your personal favorites list.
- 📚 **Book Borrowing** — Submit borrowing requests and wait for administrator approval.
- ✅ **Loan Confirmation** — Confirm approved borrowing requests before accessing books.
- 📖 **Online Reading** — Open and read borrowed books through the application.
- ⏳ **Late Fee Management** — Calculate late fees based on the borrowing period.
- 💳 **Payment Interface** — Provides a card payment form for eligible outstanding fines.
- ❓ **Help Center** — Provides instructions and guidance on using the library system.

## 📚 Borrowing Process

1. Select a book and click **"Request a Loan"**.
2. Wait for administrator approval.
3. Once approved, click **"Confirm Loan"**.
4. Click **"Read Book"** to access the borrowed book.

## ⏳ Late Fee Policy

The borrowing period is 7 days. After this period, a fee of €5 is charged for each additional day.

<table>
  <thead>
    <tr>
      <th>Borrowing Duration</th>
      <th>Late Days</th>
      <th>Fee</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td>7 days</td>
      <td>0</td>
      <td>€0</td>
    </tr>
    <tr>
      <td>12 days</td>
      <td>5</td>
      <td>€25</td>
    </tr>
    <tr>
      <td>14 days</td>
      <td>7</td>
      <td>€35</td>
    </tr>
    <tr>
      <td>16 days</td>
      <td>9</td>
      <td>€45</td>
    </tr>
  </tbody>
</table>

## 💳 Card Payment Interface

The application provides a payment form for users with outstanding fines. The interface includes fields for the cardholder's name, card number, expiration date, and CVV.

The payment button is enabled only when the user has an eligible outstanding fine.

**Security Note:** Actual card payments should be processed through a secure payment provider. Card details and CVV must not be stored directly by the application.

## ❓ Help Center

The Help Center provides guidance on browsing books, managing favorites, borrowing books, reading borrowed books, and understanding late fees.

## 🛠️ Technologies Used

<table>
  <tr>
    <td align="center">
      <img src="https://skillicons.dev/icons?i=html" width="40" alt="HTML5">
    </td>
    <td><strong>HTML5</strong> — Structures the web pages.</td>
  </tr>
  <tr>
    <td align="center">
      <img src="https://skillicons.dev/icons?i=css" width="40" alt="CSS3">
    </td>
    <td><strong>CSS3</strong> — Styles the interface and improves the visual design.</td>
  </tr>
  <tr>
    <td align="center">
      <img src="https://skillicons.dev/icons?i=js" width="40" alt="JavaScript">
    </td>
    <td><strong>JavaScript</strong> — Adds interactive functionality to the application.</td>
  </tr>
  <tr>
    <td align="center">
      <img src="https://skillicons.dev/icons?i=php" width="40" alt="PHP">
    </td>
    <td><strong>PHP</strong> — Handles server-side logic and database communication.</td>
  </tr>
  <tr>
    <td align="center">
      <img src="https://skillicons.dev/icons?i=mysql" width="40" alt="MySQL">
    </td>
    <td><strong>MySQL</strong> — Stores and manages application data.</td>
  </tr>
</table>
