@echo off
setlocal

set /p "MAGICPRO_TOKEN=Enter MAGICPRO_TOKEN: "

call codex resume --last

endlocal
