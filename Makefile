all: tags

tags: *.php code/*.php code/wfpl/*.php *.js
	exuberant-ctags -a $?
