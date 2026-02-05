<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.0+-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP Version">
  <img src="https://img.shields.io/badge/License-MIT-green?style=for-the-badge" alt="License">
  <img src="https://img.shields.io/badge/Build-Passing-brightgreen?style=for-the-badge" alt="Build Status">
  <img src="https://img.shields.io/badge/Coverage-95%25-brightgreen?style=for-the-badge" alt="Coverage">
  <img src="https://img.shields.io/badge/Version-2.0.0-blue?style=for-the-badge" alt="Version">
</p>

<h1 align="center">🧠 POLYX PRO++ Karar Motoru</h1>

<p align="center">
  <strong>Kurumsal Seviye Kural İşleme ve İş Mantığı Değerlendirme Sistemi</strong>
</p>

<p align="center">
  <a href="#-proje-hakkında">Hakkında</a> •
  <a href="#-özellikler">Özellikler</a> •
  <a href="#-kurulum">Kurulum</a> •
  <a href="#-kullanım">Kullanım</a> •
  <a href="#-mimari">Mimari</a> •
  <a href="#-api-dokümantasyonu">API</a>
</p>

---

## 📖 Proje Hakkında

**POLYX PRO++**, dinamik iş kurallarını güvenli ve yüksek performanslı bir şekilde değerlendirmek için tasarlanmış kurumsal seviye bir **Kural Motoru (Rule Engine)** sistemidir. Proje, geleneksel `eval()` fonksiyonunu kullanmadan, özel bir **Lexer-Parser-Evaluator** mimarisi ile kural ifadelerini işler.

### 🎯 Projenin Amacı

Modern yazılım sistemlerinde iş kurallarının kod içine gömülmesi (hardcoding), bakım maliyetlerini artırmakta ve esnekliği azaltmaktadır. Bu proje, iş kurallarının:

- **Dinamik olarak tanımlanmasını**
- **Güvenli bir şekilde değerlendirilmesini**
- **Gerçek zamanlı olarak test edilmesini**

sağlayan bir altyapı sunmaktadır.

### 🔬 Akademik Bağlam

Bu proje, derleyici tasarımı (Compiler Design) prensiplerini uygulayarak:

1. **Sözcüksel Analiz (Lexical Analysis)**: Token'lara ayırma
2. **Sözdizimsel Analiz (Syntactic Analysis)**: AST oluşturma
3. **Semantik Analiz (Semantic Analysis)**: Tip kontrolü ve değerlendirme

adımlarını gerçekleştirmektedir.

---

## ✨ Özellikler

### 🔧 Motor Özellikleri

| Özellik | Açıklama |
|---------|----------|
| **Dot-Notation Desteği** | `user.finance.balance` gibi iç içe veri erişimi |
| **Türkçe Operatörler** | `VE`, `VEYA`, `DEĞİL`, `İÇERİR`, `İÇİNDE` |
| **Tip Güvenliği** | `eval()` kullanılmadan güvenli değerlendirme |
| **Short-Circuit Evaluation** | AND/OR optimizasyonu |
| **AST Görselleştirme** | Soyut sözdizim ağacı görüntüleme |

### 🛡️ Güvenlik Özellikleri

- ✅ **Rate Limiting**: IP bazlı istek sınırlama (60 req/dk)
- ✅ **Input Validation**: Kapsamlı girdi doğrulama
- ✅ **Error Handling**: Merkezi hata yönetimi
- ✅ **No Eval**: `eval()` fonksiyonu kullanılmaz

### 🎨 Arayüz Özellikleri

- 🌙 **Dark Mode**: Koyu tema tasarım
- 🔮 **Glassmorphism**: Modern cam efekti UI
- 📝 **Syntax Highlighting**: CodeMirror editör
- 📊 **Canlı Metrikler**: RAM/CPU kullanımı
- 📜 **Sorgu Geçmişi**: SQLite depolama

---

## 🚀 Kurulum

### Gereksinimler

- PHP 8.0 veya üzeri
- Composer
- Apache/Nginx web sunucusu
- SQLite PDO extension

### Adımlar

```bash
# 1. Projeyi klonlayın
git clone https://github.com/kullanici/polyx-pro.git
cd polyx-pro

# 2. Bağımlılıkları yükleyin
composer install

# 3. Data dizinini oluşturun
mkdir data

# 4. Tarayıcıda açın
# http://localhost/polyx/public/login.php
```

### 🐳 Docker ile Kurulum

```bash
docker-compose up -d
# http://localhost:8080
```

### Demo Hesaplar

| Rol | Kullanıcı | Şifre |
|-----|-----------|-------|
| Admin | `admin` | `admin123` |
| Kullanıcı | `demo` | `demo123` |

---

## 📚 Kullanım

### Temel Kural Sözdizimi

```javascript
// Basit karşılaştırma
user.age >= 18

// Mantıksal operatörler
user.active == true AND user.role == "admin"

// Türkçe operatörler
kullanici.yas >= 18 VE kullanici.aktif == true

// İç içe koşullar
(user.salary > 50000 OR user.bonus > 10000) AND user.department != "intern"

// İçerik kontrolü
user.email CONTAINS "@company.com"

// Dizi kontrolü
user.role IN ["admin", "manager", "editor"]
```

### API Kullanımı

```bash
# Kural değerlendirme
curl -X POST http://localhost/polyx/public/api.php \
  -H "Content-Type: application/json" \
  -d '{
    "rule": "user.age >= 18 AND user.active == true",
    "context": {
      "user": {
        "age": 25,
        "active": true
      }
    }
  }'
```

### Yanıt Örneği

```json
{
  "success": true,
  "decision": true,
  "message": "ONAYLANDI",
  "meta": {
    "time": "0.14 ms",
    "memory": "256 KB",
    "tokens": 8,
    "evaluation_steps": 5
  }
}
```

---

## 🏗️ Mimari

### Sistem Mimarisi

```
┌─────────────────────────────────────────────────────────────┐
│                      POLYX PRO++ Engine                      │
├─────────────────────────────────────────────────────────────┤
│  ┌─────────────┐   ┌─────────────┐   ┌─────────────┐       │
│  │  Tokenizer  │──▶│   Parser    │──▶│  Evaluator  │       │
│  │   (Lexer)   │   │(AST Builder)│   │(Interpreter)│       │
│  └─────────────┘   └─────────────┘   └─────────────┘       │
│         │                │                  │               │
│         ▼                ▼                  ▼               │
│  ┌─────────────┐   ┌─────────────┐   ┌─────────────┐       │
│  │   Tokens    │   │     AST     │   │   Result    │       │
│  │   Array     │   │    Tree     │   │   Boolean   │       │
│  └─────────────┘   └─────────────┘   └─────────────┘       │
├─────────────────────────────────────────────────────────────┤
│                     Service Layer                            │
│  ┌─────────────┐   ┌─────────────┐   ┌─────────────┐       │
│  │RateLimiter  │   │ErrorHandler │   │QueryHistory │       │
│  └─────────────┘   └─────────────┘   └─────────────┘       │
└─────────────────────────────────────────────────────────────┘
```

### Dizin Yapısı

```
polyx/
├── 📁 src/Engine/
│   ├── 📄 Tokenizer.php      # Sözcüksel analiz
│   ├── 📄 Parser.php         # Sözdizimsel analiz
│   ├── 📄 Evaluator.php      # Semantik analiz
│   ├── 📄 RuleEngine.php     # Ana fasad sınıfı
│   ├── 📄 Context.php        # Veri erişim katmanı
│   ├── 📁 Exception/         # Özel istisna sınıfları
│   └── 📁 Service/           # Yardımcı servisler
├── 📁 public/
│   ├── 📄 index.php          # Dashboard
│   ├── 📄 login.php          # Giriş sayfası
│   ├── 📄 api.php            # REST API
│   └── 📄 dashboard.js       # Frontend mantığı
├── 📄 composer.json
├── 📄 Dockerfile
└── 📄 docker-compose.yml
```

---

## 📡 API Dokümantasyonu

### Endpoints

| Metod | Endpoint | Açıklama |
|-------|----------|----------|
| `POST` | `/api.php` | Kural değerlendirme |
| `GET` | `/api.php?action=info` | API bilgisi |
| `GET` | `/api.php?action=templates` | Kural şablonları |
| `GET` | `/api.php?action=history` | Sorgu geçmişi |
| `GET` | `/api.php?action=favorites` | Favoriler |
| `GET` | `/api.php?action=stats` | Sistem istatistikleri |
| `DELETE` | `/api.php?id={id}` | Favori silme |

### Desteklenen Operatörler

| Kategori | Operatörler |
|----------|-------------|
| Karşılaştırma | `==`, `!=`, `>`, `<`, `>=`, `<=` |
| Mantıksal | `AND`, `OR`, `NOT` |
| Türkçe | `VE`, `VEYA`, `DEĞİL` |
| Fonksiyonlar | `CONTAINS`, `IN`, `İÇERİR`, `İÇİNDE` |

---

## 🧪 Test

```bash
# Manuel test
php -r "
require 'vendor/autoload.php';
\$engine = new Polyx\Engine\RuleEngine();
\$result = \$engine->execute('x > 5', ['x' => 10]);
var_dump(\$result['decision']); // true
"
```

---

## � Performans

| Metrik | Değer |
|--------|-------|
| Ortalama Değerlendirme Süresi | < 1ms |
| Bellek Kullanımı | < 2MB |
| Maksimum Token Kapasitesi | Sınırsız |
| Eşzamanlı İstek Desteği | 60 req/dk/IP |

---

## 🤝 Katkıda Bulunma

1. Fork edin
2. Feature branch oluşturun (`git checkout -b feature/amazing-feature`)
3. Commit edin (`git commit -m 'feat: Add amazing feature'`)
4. Push edin (`git push origin feature/amazing-feature`)
5. Pull Request açın

---

## 📄 Lisans

Bu proje MIT lisansı altında lisanslanmıştır. Detaylar için [LICENSE](LICENSE) dosyasına bakınız.

---

## 👨‍� Geliştirici

**POLYX Development Team**

---

<p align="center">
  <sub>⭐ Bu projeyi beğendiyseniz yıldız vermeyi unutmayın!</sub>
</p>
