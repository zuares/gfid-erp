const fs = require('fs');
const code = fs.readFileSync('resources/views/marketplace/ads.blade.php', 'utf8');

// extract the JS between <script> and </script>
const scriptStart = code.indexOf('<script>');
const scriptEnd = code.lastIndexOf('</script>');
const script = code.slice(scriptStart + 8, scriptEnd);

fs.writeFileSync('extracted_script.js', script);
