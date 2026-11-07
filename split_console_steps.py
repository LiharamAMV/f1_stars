import os
import sys

sys.stdout.reconfigure(encoding='utf-8')

with open(r"c:\F1 Stars\f1_stars\db\compact_statements.sql", "r", encoding="utf-8") as f:
    text = f.read()

blocks = text.split('-- PASO ')

def clean(b):
    # remove first line (the number)
    lines = b.strip().split('\n')
    return '\n'.join(lines[1:]).strip()

os.makedirs(r"c:\F1 Stars\f1_stars\db\web_console", exist_ok=True)

# 1. PARTICIPANTES Y SETTINGS
p1 = clean(blocks[2]) + "\n" + clean(blocks[12])
with open(r"c:\F1 Stars\f1_stars\db\web_console\1_participantes.sql", "w", encoding="utf-8") as f:
    f.write(p1)

# 2. PILOTOS
p2 = clean(blocks[4])
with open(r"c:\F1 Stars\f1_stars\db\web_console\2_pilotos.sql", "w", encoding="utf-8") as f:
    f.write(p2)

# 3. SESIONES
p3 = clean(blocks[6])
with open(r"c:\F1 Stars\f1_stars\db\web_console\3_sesiones.sql", "w", encoding="utf-8") as f:
    f.write(p3)

# 4. RESULTADOS
p4 = clean(blocks[8])
with open(r"c:\F1 Stars\f1_stars\db\web_console\4_resultados.sql", "w", encoding="utf-8") as f:
    f.write(p4)

# 5. APUESTAS
p5 = clean(blocks[10])
with open(r"c:\F1 Stars\f1_stars\db\web_console\5_apuestas.sql", "w", encoding="utf-8") as f:
    f.write(p5)

print("Regenerated clean files in db/web_console/!")
