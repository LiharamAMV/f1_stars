with open(r"c:\F1 Stars\f1_stars\db\seed.sql", "r", encoding="utf-8") as f:
    sql = f.read()

# Escape backticks and template string syntax if any
escaped_sql = sql.replace("\\", "\\\\").replace("`", "\\`").replace("${", "\\${")

content = f"""// Auto-initialization SQL for Cloudflare D1
export const SEED_SQL = `{escaped_sql}`;

export async function ensureDatabase(db) {{
    try {{
        // Check if participants table exists
        const check = await db.prepare("SELECT count(*) as count FROM participants").first().catch(() => null);
        if (!check || check.count === 0) {{
            console.log("Initializing database with seed data...");
            await db.exec(SEED_SQL);
            console.log("Database initialized successfully!");
        }}
    }} catch (err) {{
        console.error("Error ensuring database:", err);
        try {{
            await db.exec(SEED_SQL);
        }} catch (e) {{
            console.error("Exec fallback error:", e);
        }}
    }}
}}
"""

with open(r"c:\F1 Stars\f1_stars\functions/api/_init_sql.js", "w", encoding="utf-8") as f:
    f.write(content)

print("Created functions/api/_init_sql.js successfully!")
