const fs = require('fs');
const path = '/Users/ariefmuhamad/Herd/gfid-dev/resources/views/marketplace/ads.blade.php';
let content = fs.readFileSync(path, 'utf8');

const regex = /const delta = prev \? r\.balance - prev\.balance : null;\s*return `<tr style="border-top:1px solid #f1f5f9">\s*<td style="padding:4px">\$\{r\.date\}<\/td>/;

const replacement = `const delta = prev ? r.balance - prev.balance : null;
                        const _m = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
                        const fmtDt = (d) => { if (!d) return '—'; const p = d.split('-'); return p.length===3 ? \`\${p[2]} \${_m[parseInt(p[1])-1]} \${p[0]}\` : d; };
                        return \`<tr style="border-top:1px solid #f1f5f9">
                            <td style="padding:4px">\${fmtDt(r.date)}</td>\``;

if (content.match(regex)) {
    content = content.replace(regex, replacement);
    fs.writeFileSync(path, content);
    console.log("History popup date formatted successfully");
} else {
    console.log("Regex did not match for history popup");
}
