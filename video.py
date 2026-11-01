import os
import uuid
import asyncio
import yt_dlp
from telegram import Update, InlineKeyboardButton, InlineKeyboardMarkup
from telegram.request import HTTPXRequest
from telegram.ext import Application, CommandHandler, MessageHandler, CallbackQueryHandler, filters, ContextTypes

TOKEN = os.environ.get("BOT_TOKEN", "9210797191:AAH70wcGejlAFyrFV7FGkslbMMFMu_b7qt0")
DOWNLOAD_DIR = "downloads"
os.makedirs(DOWNLOAD_DIR, exist_ok=True)

def download_video(url: str, progress_cb=None) -> dict:
    file_id = str(uuid.uuid4())
    outtmpl = os.path.join(DOWNLOAD_DIR, f"{file_id}.%(ext)s")

    def hook(d):
        if progress_cb and d.get("status") == "downloading":
            total = d.get("total_bytes") or d.get("total_bytes_estimate") or 0
            if total > 0:
                progress_cb(d.get("downloaded_bytes", 0) / total)

    ydl_opts = {
        "format": "bv*+ba/b",
        "merge_output_format": "mp4",
        "outtmpl": outtmpl,
        "noplaylist": True,
        "quiet": True,
        "no_warnings": True,
        "retries": 10,
        "fragment_retries": 10,
        "concurrent_fragment_downloads": 10,
        "progress_hooks": [hook],
        "extractor_args": {"youtube": {"player_client": ["android", "web"]}},
    }

    with yt_dlp.YoutubeDL(ydl_opts) as ydl:
        info = ydl.extract_info(url, download=True)
        for ext in ("mp4", "mkv", "webm", "flv", "mov"):
            path = os.path.join(DOWNLOAD_DIR, f"{file_id}.{ext}")
            if os.path.exists(path):
                return {
                    "path": path,
                    "title": info.get("title", "video"),
                    "platform": info.get("extractor_key", "Unknown"),
                    "size_mb": os.path.getsize(path) / (1024 * 1024),
                }
        raise FileNotFoundError("لم يتم العثور على ملف الفيديو")

async def start(update: Update, context: ContextTypes.DEFAULT_TYPE):
    user_name = update.effective_user.first_name
    text = (
        f"مرحباً ({user_name}) 👋\n"
        "➖➖➖➖➖➖➖➖➖➖\n\n"
        "⚡ أرسل أي رابط وسأحمله لك بأقصى سرعة وجودة\n\n"
        "المنصات المدعومة:\n"
        "📸 Instagram • 🐦 X (Twitter)\n"
        "🎵 TikTok • ▶️ YouTube\n"
        "👻 Snapchat • 📌 Pinterest\n"
        "🧵 Threads\n\n"
        "➖➖➖➖➖➖➖➖➖➖\n\n"
        "📖 داخل المجموعات:\n"
        "/d رابط ← تحميل سريع\n"
        "/v رابط ← فيديو يوتيوب\n\n"
        "أو رد على رسالة بـ /d"
    )

    keyboard = [
        [InlineKeyboardButton("اليوتيوب", callback_data="btn_youtube")],
        [
            InlineKeyboardButton("الانستكرام", callback_data="btn_insta"),
            InlineKeyboardButton("الفيسبوك", callback_data="btn_fb")
        ],
        [
            InlineKeyboardButton("الايكي", callback_data="btn_likee"),
            InlineKeyboardButton("التيك توك", callback_data="btn_tiktok")
        ],
        [
            InlineKeyboardButton("سناب جات", callback_data="btn_snap"),
            InlineKeyboardButton("تويتر", callback_data="btn_twitter")
        ],
        [
            InlineKeyboardButton("تورنت", callback_data="btn_torrent"),
            InlineKeyboardButton("بنترست", callback_data="btn_pinterest")
        ],
        [InlineKeyboardButton("🌐 أي موقع", callback_data="btn_any")],
        [InlineKeyboardButton("📊 إحصائياتي", callback_data="btn_stats")],
        [InlineKeyboardButton("➕ أضف البوت لمجموعتك", url="https://t.me/YOUR_BOT_USERNAME?startgroup=true")]
    ]
    reply_markup = InlineKeyboardMarkup(keyboard)
    await update.message.reply_text(text, reply_markup=reply_markup)

async def button_callback(update: Update, context: ContextTypes.DEFAULT_TYPE):
    query = update.callback_query
    await query.answer()
    if query.data == "btn_stats":
        await query.message.reply_text("📊 إحصائياتك:\nلقد قمت بتحميل 0 فيديو حتى الآن.")
    elif query.data == "btn_any":
        await query.message.reply_text("🌐 أرسل الرابط الآن.")
    else:
        await query.message.reply_text("أرسل الرابط الآن للتحميل.")

async def handle_url(update: Update, context: ContextTypes.DEFAULT_TYPE):
    text = update.message.text
    url = ""
    
    if text.startswith("/d ") or text.startswith("/v "):
        url = text.split(" ", 1)[1].strip()
    elif update.message.reply_to_message and text == "/d":
        replied_text = update.message.reply_to_message.text
        if replied_text:
            for word in replied_text.split():
                if word.startswith("http"):
                    url = word
                    break
    elif text.startswith(("http://", "https://")):
        url = text.strip()

    if not url:
        return

    msg = await update.message.reply_text("⚡ جارٍ التحميل السريع...")
    loop = asyncio.get_running_loop()
    last_pct = [0]

    def on_progress(pct):
        if pct - last_pct[0] >= 0.2:
            last_pct[0] = pct
            asyncio.run_coroutine_threadsafe(
                msg.edit_text(f"⚡ جارٍ التحميل... {pct*100:.0f}%"), loop
            )

    file_path = None
    try:
        result = await loop.run_in_executor(None, download_video, url, on_progress)
        file_path = result["path"]
        size_mb = result["size_mb"]

        await msg.edit_text(f"✅ اكتمل التحميل | الحجم: {size_mb:.1f}MB")

        try:
            with open(file_path, "rb") as f:
                await update.message.reply_video(
                    video=f,
                    caption=f"🎬 {result['title'][:100]}\n\n🔗 {url}",
                    supports_streaming=True,
                    read_timeout=180,
                    write_timeout=180,
                )
        except Exception as e:
            if "Request Entity Too Large" in str(e) or "too large" in str(e).lower():
                await msg.edit_text("⚠️ الحجم كبير على تيليجرام كفيديو، جاري إرساله كمستند...")
                with open(file_path, "rb") as f:
                    await update.message.reply_document(
                        document=f,
                        filename=f"{result['title'][:50]}.mp4",
                        caption=f"📁 {result['title'][:100]}\n\n🔗 {url}"
                    )
            else:
                raise e

        await msg.delete()

    except Exception as e:
        await msg.edit_text(f"❌ فشل الإرسال: {str(e)[:200]}")
    finally:
        if file_path and os.path.exists(file_path):
            try:
                os.remove(file_path)
            except Exception:
                pass

def main():
    req = HTTPXRequest(
        connection_pool_size=32,
        connect_timeout=30.0, 
        read_timeout=180.0,
        write_timeout=180.0
    )
    app = Application.builder().token(TOKEN).request(req).get_updates_request(req).build()
    
    app.add_handler(CommandHandler("start", start))
    app.add_handler(CallbackQueryHandler(button_callback))
    app.add_handler(MessageHandler(filters.TEXT & ~filters.COMMAND, handle_url))
    app.add_handler(CommandHandler("d", handle_url))
    app.add_handler(CommandHandler("v", handle_url))
    
    print("البوت يعمل بكامل قوته...")
    app.run_polling()

if __name__ == "__main__":
    main()