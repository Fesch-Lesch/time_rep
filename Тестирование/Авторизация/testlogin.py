import json
import os
import shutil
import glob
from datetime import datetime
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from fpdf import FPDF

# --- КОНСТАНТЫ ---
CONFIG_FILE = "test_data.json"
FONT_FILE = "times.ttf"

# --- 1. ПРОВЕРКА ОКРУЖЕНИЯ ---
if not os.path.exists(CONFIG_FILE):
    print(f"❌ Error: File {CONFIG_FILE} not found")
    exit()
if not os.path.exists(FONT_FILE):
    print(f"❌ Error: Font file {FONT_FILE} not found.")
    exit()

# Load JSON
with open(CONFIG_FILE, 'r', encoding='utf-8') as f:
    data = json.load(f)

SETTINGS = data['settings']
TESTS = data['tests']

# Directories
IMG_DIR = SETTINGS['temp_screenshots']
if os.path.exists(IMG_DIR): shutil.rmtree(IMG_DIR)
os.makedirs(IMG_DIR)

# --- 2. PDF SETUP ---
class ReportPDF(FPDF):
    def header(self): pass
    def footer(self): pass

# --- 3. ТЕСТИРОВАНИЕ ---
def run_test_cycle():
    options = webdriver.ChromeOptions()
    # options.add_argument("--headless") 
    driver = webdriver.Chrome(options=options)
    driver.set_window_size(1200, 900)

    results = []

    try:
        print(f"🚀 Starting tests on {SETTINGS['login_url']}...")
        
        for i, test in enumerate(TESTS, 1):
            desc = test['description']
            login = test['login']
            password = test['password']
            expect_success = test['expect_success']
            
            print(f"🔹 Test {i}: {desc}...", end=" ")
            
            try:
                # 1. Navigation
                driver.get(SETTINGS['login_url'])
                
                # Ждем появления формы входа
                WebDriverWait(driver, 5).until(EC.visibility_of_element_located((By.ID, "username")))
                
                # 2. Input Data
                driver.find_element(By.ID, "username").clear()
                if login: driver.find_element(By.ID, "username").send_keys(login)
                
                driver.find_element(By.ID, "password").clear()
                if password: driver.find_element(By.ID, "password").send_keys(password)
                
                # 3. SCREENSHOT (During input)
                screen_path = f"{IMG_DIR}/test_{i}.png"
                driver.save_screenshot(screen_path)
                
                # 4. Click Button
                driver.find_element(By.CSS_SELECTOR, "button[type='submit']").click()
                
                # 5. Verification
                outcome = "НЕУДАЧА"
                details = "Unknown error"
                
                if expect_success:
                    # ПОЗИТИВНЫЙ СЦЕНАРИЙ: Ждем редиректа на dashboard
                    try:
                        WebDriverWait(driver, 5).until(EC.url_contains("dashboard.php"))
                        
                        # Ждем появления заголовка (гарантия загрузки страницы)
                        WebDriverWait(driver, 5).until(EC.visibility_of_element_located((By.TAG_NAME, "h1")))
                        
                        welcome = driver.find_element(By.TAG_NAME, "h1").text
                        if login in welcome or "Добро пожаловать" in welcome:
                            outcome = "УСПЕХ"
                            details = "Вход выполнен успешно."
                        else:
                            details = "Редирект прошел, но заголовок некорректен."
                            
                        # === ВАЖНО: ВЫХОД ИЗ СИСТЕМЫ ПОСЛЕ УСПЕШНОГО ТЕСТА ===
                        driver.get(SETTINGS['logout_url'])
                        # Ждем возврата на login.php, чтобы убедиться, что вышли
                        WebDriverWait(driver, 5).until(EC.visibility_of_element_located((By.ID, "username")))
                        
                    except Exception as e:
                        details = f"Ошибка входа: {str(e)}"
                else:
                    # НЕГАТИВНЫЙ СЦЕНАРИЙ: Ждем ошибку
                    try:
                        if login and password:
                            # Если данные введены, ждем alert-error
                            WebDriverWait(driver, 3).until(EC.visibility_of_element_located((By.CLASS_NAME, "alert-error")))
                            outcome = "УСПЕХ"
                            details = "Сообщение об ошибке отображено."
                        else:
                            # Если поля пустые (браузерная валидация), URL не должен меняться
                            if "login.php" in driver.current_url:
                                outcome = "УСПЕХ"
                                details = "Вход заблокирован (валидация)."
                    except:
                        if "dashboard.php" in driver.current_url:
                            details = "ОШИБКА: Удалось войти с неверными данными!"
                            # Если случайно вошли - выходим!
                            driver.get(SETTINGS['logout_url'])
                        else:
                            details = "Сообщение об ошибке не найдено."

                print(outcome)
                
                results.append({
                    "n": i,
                    "desc": desc,
                    "input": f"Логин: {login}, Пароль: {password}",
                    "status": outcome,
                    "details": details,
                    "img": screen_path
                })

            except Exception as e:
                print("SCRIPT ERROR")
                results.append({
                    "n": i,
                    "desc": desc,
                    "input": f"Логин: {login}",
                    "status": "ОШИБКА",
                    "details": str(e),
                    "img": None
                })

    finally:
        driver.quit()
        generate_pdf(results)

# --- 4. GENERATE PDF ---
def generate_pdf(data):
    print("\n📄 Creating PDF report...")
    
    existing_reports = glob.glob("тест_авторизации№*.pdf")
    next_num = 1
    if existing_reports:
        nums = []
        for f in existing_reports:
            try:
                part = f.split('№')[1].split('.')[0]
                nums.append(int(part))
            except: pass
        if nums: next_num = max(nums) + 1

    now = datetime.now()
    filename = f"тест_авторизации№{next_num}.{now.strftime('%d.%m.%Y.%H-%M-%S')}.pdf"

    pdf = ReportPDF(orientation='P', unit='mm', format='A4')
    pdf.set_margins(20, 10, 10) 
    pdf.set_auto_page_break(auto=True, margin=10)
    
    pdf.add_font('TimesNewRoman', '', FONT_FILE, uni=True)
    pdf.add_page()
    
    pdf.set_font('TimesNewRoman', '', 16)
    pdf.set_text_color(0, 0, 0)
    pdf.cell(0, 10, f"Отчет о тестировании №{next_num}", ln=1, align='C')
    
    pdf.set_font('TimesNewRoman', '', 12)
    pdf.cell(0, 8, f"Дата: {now.strftime('%d.%m.%Y %H:%M:%S')}", ln=1, align='C')
    pdf.ln(10)

    pdf.set_font('TimesNewRoman', '', 14)
    line_height = 8 

    for item in data:
        status_text = "УСПЕШНО" if item['status'] == "УСПЕХ" else "НЕУДАЧНО"
        
        text_block = (
            f"Тест №{item['n']}: {item['desc']}\n"
            f"Входные данные: {item['input']}\n"
            f"Статус: {status_text}\n"
            f"Детали: {item['details']}"
        )
        
        pdf.multi_cell(0, line_height, text_block, align='J')
        pdf.ln(5)

        if item['img'] and os.path.exists(item['img']):
            pdf.image(item['img'], x=25, w=160)
            pdf.ln(10)
        
        pdf.line(20, pdf.get_y(), 200, pdf.get_y())
        pdf.ln(10)

    pdf.output(filename)
    print(f"✅ Report saved: {filename}")
    
    if os.path.exists(IMG_DIR):
        shutil.rmtree(IMG_DIR)

if __name__ == "__main__":
    run_test_cycle()