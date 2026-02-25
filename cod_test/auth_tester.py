import os
import json
import shutil
import glob
from datetime import datetime
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from config import Config
from base_pdf import BasePDF

class AuthTester:
    def __init__(self, config_file=None, reports_dir=None):
        self.config = Config()  # создаём объект конфига для доступа к resolve_path
        self.config_file = config_file or self.config.AUTH_CONFIG_FILE
        self.reports_dir = reports_dir or self.config.REPORTS_DIR
        self.temp_dir = self.config.AUTH_TEMP_SCREENS
        self.results = []
        self.login_url = None
        self.logout_url = None
        
    def _prompt_for_config(self):
        while True:
            path = input("Введите путь к файлу конфигурации (или 'new' для создания нового): ").strip()
            if path.lower() == 'new':
                default_path = self.config.AUTH_CONFIG_FILE
                print(f"Будет создан новый файл: {default_path}")
                self.config_file = default_path
                self._create_default_config()
                return True
            abs_path = self.config.resolve_path(path)
            if abs_path and os.path.isfile(abs_path):
                self.config_file = abs_path
                return True
            else:
                create = input(f"Файл '{path}' не найден. Создать новый? (y/n): ").strip().lower()
                if create == 'y':
                    self.config_file = path
                    self._create_default_config()
                    return True
                else:
                    print("Попробуйте снова.")
    
    def _prompt_for_urls(self):
        print("\n--- Введите настройки подключения ---")
        default_login = "http://localhost:3000/dnd-site/login.php"
        default_logout = "http://localhost:3000/dnd-site/logout.php"
        login = input(f"Login URL (Enter = '{default_login}'): ").strip()
        self.login_url = login if login else default_login
        logout = input(f"Logout URL (Enter = '{default_logout}'): ").strip()
        self.logout_url = logout if logout else default_logout
        print(f"✅ Используются: login={self.login_url}, logout={self.logout_url}")
    
    def setup(self):
        if not os.path.exists(self.config_file):
            print(f"❌ Файл конфигурации '{self.config_file}' не найден.")
            if not self._prompt_for_config():
                return False
        else:
            if os.path.isdir(self.config_file):
                print(f"❌ Указанный путь '{self.config_file}' является папкой. Нужен файл.")
                if not self._prompt_for_config():
                    return False
        
        if os.path.exists(self.temp_dir):
            shutil.rmtree(self.temp_dir)
        os.makedirs(self.temp_dir)
        return True
    
    def _create_default_config(self):
        config = {
            "settings": {
                "login_url": "http://localhost:3000/dnd-site/login.php",
                "logout_url": "http://localhost:3000/dnd-site/logout.php",
                "report_folder": "reports",
                "temp_screenshots": "temp_screens"
            },
            "tests": [
                {"description": "Неверный пароль", "login": "admin", "password": "wrongpassword", "expect_success": False},
                {"description": "Несуществующий пользователь", "login": "ghost", "password": "password", "expect_success": False},
                {"description": "Пустой пароль", "login": "admin", "password": "", "expect_success": False},
                {"description": "Вход: Администратор", "login": "admin", "password": "password", "expect_success": True},
                {"description": "Вход: Учитель", "login": "teacher1", "password": "password", "expect_success": True},
                {"description": "Вход: Капитан", "login": "captain_red", "password": "password", "expect_success": True}
            ]
        }
        with open(self.config_file, 'w', encoding='utf-8') as f:
            json.dump(config, f, ensure_ascii=False, indent=2)
        print(f"✅ Создан новый файл конфигурации: {self.config_file}")
    
    def load_config(self):
        try:
            with open(self.config_file, 'r', encoding='utf-8') as f:
                raw = json.load(f)
        except Exception as e:
            print(f"❌ Ошибка загрузки {self.config_file}: {e}")
            return None
        
        if isinstance(raw, dict) and "settings" in raw and "tests" in raw:
            settings = raw["settings"]
            self.login_url = settings.get("login_url")
            self.logout_url = settings.get("logout_url")
            tests = raw["tests"]
            print("✅ Загружен полный формат конфигурации.")
            return tests
        
        elif isinstance(raw, dict) and "urls" in raw and "users" in raw:
            print("⚠️ Обнаружен старый формат (urls/users). Преобразую...")
            self.login_url = raw["urls"]["login"]
            self.logout_url = raw["urls"]["logout"]
            common_pwd = raw.get("common_password", "password")
            tests = []
            for user in raw["users"]:
                login = user["login"]
                role = user.get("expected_role", "user")
                pwd = user.get("password", common_pwd)
                tests.append({
                    "description": f"Вход: {login} ({role})",
                    "login": login,
                    "password": pwd,
                    "expect_success": True
                })
            print(f"✅ Преобразовано. Login URL: {self.login_url}, Logout URL: {self.logout_url}")
            return tests
        
        elif isinstance(raw, dict) and "tests" in raw:
            tests = raw["tests"]
            print("⚠️ Файл содержит только тесты. Настройки URL будут запрошены.")
            if not self.login_url or not self.logout_url:
                self._prompt_for_urls()
            return tests
        
        elif isinstance(raw, list):
            print("⚠️ Файл содержит список тестов. Настройки URL будут запрошены.")
            if not self.login_url or not self.logout_url:
                self._prompt_for_urls()
            return raw
        
        else:
            print("❌ Неизвестный формат файла.")
            return None
    
    def run_test(self):
        print("\n" + "="*60)
        print("🔐 ТЕСТИРОВАНИЕ АВТОРИЗАЦИИ")
        print("="*60)
        
        tests = self.load_config()
        if tests is None:
            return False
        
        if not self.login_url or not self.logout_url:
            self._prompt_for_urls()
        
        print(f"📋 Тестов: {len(tests)}")
        print(f"🌐 Login URL: {self.login_url}")
        print(f"🌐 Logout URL: {self.logout_url}")
        print(f"📁 Файл конфигурации: {self.config_file}\n")
        
        options = webdriver.ChromeOptions()
        options.add_argument("--window-size=1200,900")
        # options.add_argument("--headless")
        driver = None
        
        try:
            driver = webdriver.Chrome(options=options)
            for i, test in enumerate(tests, 1):
                if not all(k in test for k in ('description', 'login', 'password', 'expect_success')):
                    print(f"⚠️ Тест {i} пропущен: не все поля")
                    continue
                
                print(f"🔹 Тест {i}: {test['description']}...", end=" ")
                try:
                    driver.get(self.login_url)
                    WebDriverWait(driver, 5).until(EC.presence_of_element_located((By.ID, "username")))
                    
                    username = driver.find_element(By.ID, "username")
                    password = driver.find_element(By.ID, "password")
                    username.clear()
                    if test['login']: username.send_keys(test['login'])
                    password.clear()
                    if test['password']: password.send_keys(test['password'])
                    
                    screen_before = os.path.join(self.temp_dir, f"test_{i}_before.png")
                    driver.save_screenshot(screen_before)
                    
                    driver.find_element(By.CSS_SELECTOR, "button[type='submit']").click()
                    
                    result, details = self._verify_result(driver, test)
                    
                    screen_after = os.path.join(self.temp_dir, f"test_{i}_after.png")
                    driver.save_screenshot(screen_after)
                    
                    if "УСПЕХ" in result and test['expect_success']:
                        try:
                            driver.get(self.logout_url)
                            WebDriverWait(driver, 3).until(EC.presence_of_element_located((By.ID, "username")))
                        except: pass
                    
                    print(result)
                    self.results.append({
                        "num": i,
                        "description": test['description'],
                        "input": f"Логин: {test['login']}, Пароль: {'*'*len(test['password']) if test['password'] else '[пусто]'}",
                        "status": result,
                        "details": details,
                        "screenshots": [screen_before, screen_after] if os.path.exists(screen_after) else [screen_before]
                    })
                except Exception as e:
                    print("ОШИБКА")
                    self.results.append({
                        "num": i,
                        "description": test['description'],
                        "input": f"Логин: {test['login']}",
                        "status": "ОШИБКА",
                        "details": str(e),
                        "screenshots": []
                    })
            return True
        finally:
            if driver: driver.quit()
    
    def _verify_result(self, driver, test):
        if test['expect_success']:
            try:
                WebDriverWait(driver, 5).until(EC.url_contains("dashboard.php"))
                return "УСПЕХ", "Вход выполнен"
            except:
                return "ПРОВАЛ", "Редирект не произошёл"
        else:
            try:
                if test['login'] and test['password']:
                    WebDriverWait(driver, 3).until(EC.visibility_of_element_located((By.CLASS_NAME, "alert-error")))
                    return "УСПЕХ", "Сообщение об ошибке"
                else:
                    if "login.php" in driver.current_url:
                        return "УСПЕХ", "Вход заблокирован"
                    else:
                        return "ПРОВАЛ", "Переход при пустых полях"
            except:
                if "dashboard.php" in driver.current_url:
                    return "ПРОВАЛ", "Удалось войти неверно"
                else:
                    return "ПРОВАЛ", "Сообщение об ошибке не найдено"
    
    def generate_report(self):
        print("\n📄 Создание PDF отчёта...")
        os.makedirs(self.reports_dir, exist_ok=True)
        
        pattern = os.path.join(self.reports_dir, "тест_авторизации№*.pdf")
        existing = glob.glob(pattern)
        next_num = 1
        if existing:
            nums = []
            for f in existing:
                try:
                    num = int(os.path.basename(f).split('№')[1].split('.')[0])
                    nums.append(num)
                except: pass
            if nums: next_num = max(nums) + 1
        
        pdf = BasePDF(self.config.FONT_PATH)
        pdf.add_page()
        pdf.add_custom_font()
        
        pdf.set_font('TimesRus', '', 16)
        pdf.cell(0, 10, f"Отчёт о тестировании авторизации №{next_num}", ln=1, align='C')
        pdf.set_font('TimesRus', '', 12)
        pdf.cell(0, 8, f"Дата: {datetime.now().strftime('%d.%m.%Y %H:%M:%S')}", ln=1, align='C')
        pdf.cell(0, 8, f"Файл конфигурации: {self.config_file}", ln=1, align='C')
        pdf.cell(0, 8, f"Login URL: {self.login_url}", ln=1, align='C')
        pdf.cell(0, 8, f"Logout URL: {self.logout_url}", ln=1, align='C')
        pdf.ln(10)
        
        total = len(self.results)
        success = sum(1 for r in self.results if "УСПЕХ" in r['status'])
        failed = total - success
        pdf.cell(0, 8, f"Всего тестов: {total}", ln=1)
        pdf.set_text_color(0,150,0)
        pdf.cell(0, 8, f"Успешно: {success}", ln=1)
        pdf.set_text_color(255,0,0)
        pdf.cell(0, 8, f"Провалено: {failed}", ln=1)
        pdf.set_text_color(0,0,0)
        pdf.ln(10)
        
        pdf.set_font('TimesRus', '', 11)
        line_h = 7
        for r in self.results:
            status_color = (0,150,0) if "УСПЕХ" in r['status'] else (255,0,0) if "ПРОВАЛ" in r['status'] else (255,140,0)
            pdf.set_text_color(0,0,0)
            pdf.multi_cell(0, line_h, f"Тест №{r['num']}: {r['description']}")
            pdf.multi_cell(0, line_h, f"Входные данные: {r['input']}")
            pdf.set_text_color(*status_color)
            pdf.multi_cell(0, line_h, f"Статус: {r['status']}")
            pdf.set_text_color(100,100,100)
            pdf.multi_cell(0, line_h, f"Детали: {r['details']}")
            pdf.set_text_color(0,0,0)
            pdf.ln(3)
            for i, sc in enumerate(r.get('screenshots',[])):
                if os.path.exists(sc):
                    caption = "Скриншот после ввода" if "after" in sc else "Скриншот перед отправкой"
                    pdf.set_font('TimesRus', '', 9)
                    pdf.cell(0, 5, caption, ln=1, align='C')
                    pdf.image(sc, x=25, w=160)
                    pdf.ln(5)
            pdf.line(20, pdf.get_y(), 190, pdf.get_y())
            pdf.ln(10)
        
        timestamp = datetime.now().strftime('%d.%m.%Y.%H-%M-%S')
        filename = os.path.join(self.reports_dir, f"тест_авторизации№{next_num}.{timestamp}.pdf")
        pdf.output(filename)
        print(f"✅ Отчёт сохранён: {filename}")
        return filename
    
    def cleanup(self):
        if os.path.exists(self.temp_dir):
            shutil.rmtree(self.temp_dir)
    
    def execute(self):
        try:
            if not self.setup():
                print("❌ Не удалось настроить тестирование.")
                return False
            if not self.run_test():
                return False
            report = self.generate_report()
            self.cleanup()
            print(f"\n✅ Тестирование авторизации завершено. Отчёт: {report}")
            return True
        except Exception as e:
            print(f"\n❌ Ошибка: {e}")
            return False