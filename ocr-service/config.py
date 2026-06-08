"""
Configuration Management
"""
from dataclasses import dataclass
from typing import List


@dataclass
class Settings:
    # Database
    DB_HOST: str = "localhost"
    DB_PORT: int = 3306
    DB_USER: str = "root"
    DB_PASSWORD: str = ""
    DB_NAME: str = "dbsims"
    
    # OCR
    OCR_LANG: str = "id"
    OCR_USE_GPU: bool = False
    OCR_CONFIDENCE_THRESHOLD: float = 0.5
    
    # Validation
    SCHOOL_ACCOUNT_NAME: str = "SMK BIT BINA AULIA"
    SCHOOL_ACCOUNT_NUMBER: str = "218001000867569"
    # Hanya menerima rekening tujuan BRI untuk pembayaran sekolah
    ALLOWED_BANKS: str = "BRI"
    MAX_DATE_DIFFERENCE_DAYS: int = 7
    ADMIN_AMOUNT_TOLERANCE: float = 2500
    RECIPIENT_NAME_KEYWORDS: str = "nama,tujuan,penerima,kepada,atas nama,recipient,destination"
    # Common footer phrases that appear on bank receipts and should not be treated as recipient names
    RECIPIENT_FOOTER_BLACKLIST: str = "terdaftar,diawasi,otoritas,jasa keuangan,terdaftar dan diawasi,pt bank,©"
    
    # Upload
    UPLOAD_DIR: str = "../storage/app/payments"
    MAX_FILE_SIZE: int = 5 * 1024 * 1024  # 5MB
    ALLOWED_EXTENSIONS: str = "jpg,jpeg,png"
    
    # API
    API_HOST: str = "0.0.0.0"
    API_PORT: int = 8002
    DEBUG: bool = True
    
    @property
    def database_url(self) -> str:
        return f"mysql+pymysql://{self.DB_USER}:{self.DB_PASSWORD}@{self.DB_HOST}:{self.DB_PORT}/{self.DB_NAME}"
    
    @property
    def allowed_extensions_list(self) -> List[str]:
        """Convert comma-separated string to list"""
        return [ext.strip() for ext in self.ALLOWED_EXTENSIONS.split(',')]
    
    @property
    def allowed_banks_list(self) -> List[str]:
        """Convert comma-separated string to list"""
        return [bank.strip().upper() for bank in self.ALLOWED_BANKS.split(',')]

    @property
    def recipient_name_keywords_list(self) -> List[str]:
        """Convert comma-separated recipient keywords to list"""
        return [keyword.strip().lower() for keyword in self.RECIPIENT_NAME_KEYWORDS.split(',') if keyword.strip()]

    @property
    def recipient_footer_blacklist_list(self) -> List[str]:
        """Convert comma-separated footer blacklist into list"""
        return [kw.strip().lower() for kw in self.RECIPIENT_FOOTER_BLACKLIST.split(',') if kw.strip()]


settings = Settings()
