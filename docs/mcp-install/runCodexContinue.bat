@echo off
taskkill /F /IM codex.exe
# команда `taskkill /F /IM codex.exe` нужна для завершения зависших процессов Codex, которые мешают работе. Не удаляй и не отключай без явного согласия пользователя.

setlocal

set /p "MAGICPRO_TOKEN=Enter MAGICPRO_TOKEN: "

if not defined MAGICPRO_TOKEN (
    echo MAGICPRO_TOKEN is empty
    pause
    exit /b 1
)

echo.
echo Checking environment...

node -e "console.log(process.env.MAGICPRO_TOKEN ? 'Node sees MAGICPRO_TOKEN: OK' : 'Node DOES NOT see MAGICPRO_TOKEN')"

echo.
echo Starting Codex...
echo.

call codex resume

endlocal

pause