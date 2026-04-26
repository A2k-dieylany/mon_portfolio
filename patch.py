import re

with open('sds.html', 'r', encoding='utf-8') as f:
    content = f.read()

content = re.sub(r'<style>.*?</style>', '<meta name=\"description\" content=\"Portfolio de Dieylany, expert en développement web et IA basé à Dakar, Sénégal. Découvrez les services de SEN DIGITAL SOLUTION.\"/>\n<link rel=\"stylesheet\" href=\"style.css\">', content, flags=re.DOTALL)

content = re.sub(r'<script>\s*/\* ========== I18N ========== \*/.*?</script>', '<script src=\"script.js\"></script>', content, flags=re.DOTALL)

with open('sds.html', 'w', encoding='utf-8') as f:
    f.write(content)
print('Done!')
