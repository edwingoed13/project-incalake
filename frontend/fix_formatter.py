with open('utils/formatters.ts', 'r', encoding='utf-8') as f:
    lines = f.readlines()

# Find and replace the getImageUrl function
in_getImageUrl = False
new_lines = []
skip_until_closing = False

for i, line in enumerate(lines):
    if 'export function getImageUrl' in line:
        in_getImageUrl = True
        new_lines.append(line)
        continue
    
    if in_getImageUrl:
        if 'const baseUrl' in line:
            new_lines.append("  const baseUrl = 'http://127.0.0.1:8000/storage'\n")
            skip_until_closing = False
            continue
        elif 'return `${' in line and 'http://127.0.0.1:8000' in line:
            if '/storage/' in line:
                new_lines.append("    return `http://127.0.0.1:8000${path}`\n")
            else:
                new_lines.append("    return `http://127.0.0.1:8000${path}`\n")
            continue
        elif line.strip().startswith('}') and in_getImageUrl and len(line.strip()) == 1:
            new_lines.append(line)
            in_getImageUrl = False
            continue
    
    new_lines.append(line)

with open('utils/formatters.ts', 'w', encoding='utf-8') as f:
    f.writelines(new_lines)

print("Fixed formatters.ts")
