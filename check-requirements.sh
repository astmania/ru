#!/bin/bash

###############################################################################
# Скрипт проверки системных требований
###############################################################################

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

ERRORS=0

check() {
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✓${NC} $1"
        return 0
    else
        echo -e "${RED}✗${NC} $1"
        ERRORS=$((ERRORS + 1))
        return 1
    fi
}

echo "Проверка системных требований..."
echo ""

# Проверка ОС
if [ -f /etc/os-release ]; then
    . /etc/os-release
    if [ "$ID" == "ubuntu" ]; then
        check "ОС: Ubuntu $VERSION_ID"
    else
        echo -e "${YELLOW}⚠${NC} ОС: $PRETTY_NAME (рекомендуется Ubuntu 20.04+)"
    fi
else
    echo -e "${RED}✗${NC} Не удалось определить ОС"
    ERRORS=$((ERRORS + 1))
fi

# Проверка прав root
if [ "$EUID" -eq 0 ]; then
    check "Права root: есть"
else
    echo -e "${YELLOW}⚠${NC} Права root: нет (требуются для установки)"
fi

# Проверка свободного места
AVAILABLE_SPACE=$(df -BG / | awk 'NR==2 {print $4}' | sed 's/G//')
if [ "$AVAILABLE_SPACE" -ge 2 ]; then
    check "Свободное место: ${AVAILABLE_SPACE}GB (требуется: 2GB+)"
else
    echo -e "${RED}✗${NC} Свободное место: ${AVAILABLE_SPACE}GB (требуется: 2GB+)"
    ERRORS=$((ERRORS + 1))
fi

# Проверка памяти
TOTAL_MEM=$(free -g | awk '/^Mem:/{print $2}')
if [ "$TOTAL_MEM" -ge 1 ]; then
    check "Оперативная память: ${TOTAL_MEM}GB (рекомендуется: 2GB+)"
else
    echo -e "${YELLOW}⚠${NC} Оперативная память: ${TOTAL_MEM}GB (рекомендуется: 2GB+)"
fi

# Проверка архитектуры
ARCH=$(uname -m)
if [ "$ARCH" == "x86_64" ] || [ "$ARCH" == "aarch64" ]; then
    check "Архитектура: $ARCH"
else
    echo -e "${YELLOW}⚠${NC} Архитектура: $ARCH (может не поддерживаться)"
fi

# Проверка интернета
if ping -c 1 8.8.8.8 &> /dev/null; then
    check "Интернет соединение: есть"
else
    echo -e "${RED}✗${NC} Интернет соединение: нет"
    ERRORS=$((ERRORS + 1))
fi

echo ""
if [ $ERRORS -eq 0 ]; then
    echo -e "${GREEN}Все проверки пройдены!${NC}"
    exit 0
else
    echo -e "${RED}Обнаружено ошибок: $ERRORS${NC}"
    exit 1
fi
