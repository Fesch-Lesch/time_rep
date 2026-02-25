import os
from fpdf import FPDF

class BasePDF(FPDF):
    def __init__(self, font_path: str = None):
        super().__init__()
        self.font_path = font_path
        self.font_added = False
        
    def add_custom_font(self):
        if not self.font_added and self.font_path and os.path.exists(self.font_path):
            self.add_font('TimesRus', '', self.font_path, uni=True)
            self.font_added = True
            return True
        return False
    
    def header(self):
        if self.add_custom_font():
            self.set_font('TimesRus', '', 14)
            self.cell(0, 10, 'Отчёт о тестировании DnD системы', 0, 1, 'C')
            self.ln(5)
    
    def footer(self):
        self.set_y(-15)
        if self.add_custom_font():
            self.set_font('TimesRus', '', 8)
            self.cell(0, 10, f'Страница {self.page_no()}', 0, 0, 'C')