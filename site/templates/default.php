<!doctype html>
<html class="no-js" lang="">

<head>
  <title>HTML5Video Block</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/lozad/dist/lozad.min.js"></script>

</head>

<body>
<?= $page->text()->blocks(); ?>


<script>

const el = document.querySelectorAll('[data-src]');
const observer = lozad(el); // passing a `NodeList` (e.g. `document.querySelectorAll()`) is also valid
observer.observe();

</script>
</body>

</html>



