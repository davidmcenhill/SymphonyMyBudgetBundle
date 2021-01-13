Symfony (2.3) bundle for a budgeting website

Main functionality:
- import ; allows importing of bank statements in various formats
- category : setup budget categories
- transaction : allows transactions to be viewed and to be categorised
- matches : allows for viewing / editing of the matches used to categorise a transaction.
- reports : present reports on expenditure by category and time periods.

Structure:
- Controller : classes containing business logic , web entry points map to these.
- Entity : classes to contain data from the database (using Doctrine ORM).
- Form/Type : Classes to build a form using the views below.
- Resources/views : the webpage presentation , uses Twig templates.
