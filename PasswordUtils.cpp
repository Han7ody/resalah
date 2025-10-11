// PasswordUtils.cpp
#include <iostream>
#include <string>
#include <random>
#include <sstream>
#include <iomanip>
#include <openssl/sha.h>
#include <openssl/rand.h>

class PasswordUtils {
public:
    static std::string generateSalt(int length = 16) {
        std::random_device rd;
        std::mt19937 gen(rd());
        std::uniform_int_distribution<> dis(0, 255);
        
        std::stringstream ss;
        for (int i = 0; i < length; ++i) {
            ss << std::hex << std::setw(2) << std::setfill('0') << dis(gen);
        }
        return ss.str();
    }
    
    static std::string hashPassword(const std::string& password, const std::string& salt) {
        std::string saltedPassword = password + salt;
        unsigned char hash[SHA256_DIGEST_LENGTH];
        
        SHA256_CTX sha256;
        SHA256_Init(&sha256);
        SHA256_Update(&sha256, saltedPassword.c_str(), saltedPassword.length());
        SHA256_Final(hash, &sha256);
        
        std::stringstream ss;
        for (int i = 0; i < SHA256_DIGEST_LENGTH; ++i) {
            ss << std::hex << std::setw(2) << std::setfill('0') << (int)hash[i];
        }
        return ss.str();
    }
    
    static bool verifyPassword(const std::string& password, const std::string& hash, const std::string& salt) {
        std::string hashedInput = hashPassword(password, salt);
        return hashedInput == hash;
    }
    
    static std::string generateOTP(int length = 6) {
        std::random_device rd;
        std::mt19937 gen(rd());
        std::uniform_int_distribution<> dis(0, 9);
        
        std::string otp;
        for (int i = 0; i < length; ++i) {
            otp += std::to_string(dis(gen));
        }
        return otp;
    }
    
    static bool validatePasswordStrength(const std::string& password) {
        if (password.length() < 8) return false;
        
        bool hasUpper = false, hasLower = false, hasDigit = false;
        
        for (char c : password) {
            if (c >= 'A' && c <= 'Z') hasUpper = true;
            if (c >= 'a' && c <= 'z') hasLower = true;
            if (c >= '0' && c <= '9') hasDigit = true;
        }
        
        return hasUpper && hasLower && hasDigit;
    }
};

int main() {
    // Example usage
    std::string password = "MySecurePassword123";
    std::string salt = PasswordUtils::generateSalt();
    std::string hash = PasswordUtils::hashPassword(password, salt);
    
    std::cout << "Password: " << password << std::endl;
    std::cout << "Salt: " << salt << std::endl;
    std::cout << "Hash: " << hash << std::endl;
    std::cout << "Valid: " << PasswordUtils::verifyPassword(password, hash, salt) << std::endl;
    std::cout << "OTP: " << PasswordUtils::generateOTP() << std::endl;
    std::cout << "Strong Password: " << PasswordUtils::validatePasswordStrength(password) << std::endl;
    
    return 0;
}
