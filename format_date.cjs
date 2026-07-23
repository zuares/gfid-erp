const fs = require('fs');
const path = '/Users/ariefmuhamad/Herd/gfid-dev/resources/views/marketplace/ads.blade.php';
let content = fs.readFileSync(path, 'utf8');

const regex = /const tot = \{ imp:0, clk:0, spend:0, ord:0, gmv:0 \};\s*\$\('shopPerfBody'\)\.innerHTML = days\.map\(r => \{([\s\S]*?)<td>\$\{r\.date \|\| '—'\}<\/td>/;

const replacement = `const tot = { imp:0, clk:0, spend:0, ord:0, gmv:0 };
                const _months = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
                const _fmtD = (d) => { if (!d) return '—'; const p = d.split('-'); return p.length===3 ? \`\${p[2]} \${_months[parseInt(p[1])-1]} \${p[0]}\` : d; };
                
                $('shopPerfBody').innerHTML = days.map(r => {$1<td>\${_fmtD(r.date)}</td>`;

if (content.match(regex)) {
    content = content.replace(regex, replacement);
    fs.writeFileSync(path, content);
    console.log("Date column formatted successfully");
} else {
    console.log("Regex did not match");
}
