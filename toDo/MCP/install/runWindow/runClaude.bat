@echo off
setlocal

set /p "SOLNUH_TOKEN=Enter SOLNUH_TOKEN: "

if not defined SOLNUH_TOKEN (
    echo SOLNUH_TOKEN is empty
    pause
    exit /b 1
)

echo.
echo Checking environment...

node -e "console.log(process.env.SOLNUH_TOKEN ? 'Node sees SOLNUH_TOKEN: OK' : 'Node DOES NOT see SOLNUH_TOKEN')"

echo.
echo Starting Claude Code...
echo.

call claude

endlocal
