# Baokim RSA Keys

Thư mục này chứa các file RSA key cho việc ký số và xác thực.

## Files cần có

1. **merchant_private.pem** - Private key của Merchant
   - Dùng để ký các request gửi tới Baokim
   - **BẢO MẬT**: Không chia sẻ file này

2. **merchant_public.pem** - Public key của Merchant
   - Gửi cho Baokim khi đăng ký tích hợp

3. **baokim_public.pem** - Public key của Baokim
   - Baokim cung cấp sau khi đăng ký
   - Dùng để verify signature trong webhook

## Tạo key mới

```bash
# Tạo private key (2048 bits, khuyến nghị)
openssl genrsa -out merchant_private.pem 2048

# Tạo public key từ private key
openssl rsa -in merchant_private.pem -pubout -out merchant_public.pem
```

## Bảo mật

- **KHÔNG** commit file `merchant_private.pem` lên git
- Thêm vào `.gitignore`:
  ```
  keys/merchant_private.pem
  ```
- Đặt permission phù hợp:
  ```bash
  chmod 600 merchant_private.pem
  ```
