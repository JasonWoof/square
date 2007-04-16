all: tags

tags: *.php code/*.php code/wfpl/*.php
	exuberant-ctags -a $?
