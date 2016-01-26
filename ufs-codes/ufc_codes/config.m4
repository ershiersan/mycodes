PHP_ARG_ENABLE(ufs_codes,
    [Whether to enable the "ufs_codes" extension],
    [  enable-ufs_codes        Enable "ufs_codes" extension support])

if test $PHP_UFS_CODES != "no"; then
    PHP_SUBST(UFS_CODES_SHARED_LIBADD)
    PHP_NEW_EXTENSION(ufs_codes, ufs_codes.c, $ext_shared)
fi
