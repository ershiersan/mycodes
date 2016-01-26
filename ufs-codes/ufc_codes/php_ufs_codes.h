// php_ufs_codes.h
#ifndef UFS_CODES_H
#define UFS_CODES_H

// 加载config.h
#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

// 加载php头文件
#include "php.h"
#define phpext_ufs_codes_ptr &ufs_codes_module_entry
extern zend_module_entry ufs_codes_module_entry;

#endif
