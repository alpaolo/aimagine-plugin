@ECHO OFF

SET man1=%1

git add .
git rm --cached valentina.jpg


git status

git commit -m %man1%