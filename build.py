# -*- coding: utf-8 -*-
import os
import tarfile
import argparse
import json


def get_php_modules(dir, modelus_list):
    php_modules = []
    php_list = modelus_list.split("|")
    for module_name in php_list:
        php_path = os.path.join(dir, module_name)
        if os.path.exists(php_path):
            php_modules.append(php_path)

    return php_modules


if __name__ == "__main__":
    ap = argparse.ArgumentParser(description="打包插件")
    ap.add_argument("-d", "--dir", required=False, help="自定义插件源文件所在目录")
    ap.add_argument("-m",
                    "--modules",
                    required=False,
                    default="kugeci.php|phpQuery.php",
                    help="自定义所需要打包的模块，多个时以|分隔")

    args = vars(ap.parse_args())
    build_name = "build"
    print("************************************************")
    dir_path = os.getcwd()
    if args["dir"] and os.path.exists(args["dir"]):
        dir_path = args["dir"]
    print("工作路径: " + dir_path)
    info_file = os.path.join(dir_path, "INFO")
    build_path = os.path.join(dir_path, build_name)
    if not os.path.exists(build_path):
        os.makedirs(build_path)

    if not os.path.exists(info_file):
        print("ERROR: 目录下没有找到需要打包的定义文件！")
        exit()

    php_modules = get_php_modules(dir_path, args["modules"])
    if len(php_modules) == 0:
        print("ERROR: 目录下没有找到需要打包的模块文件！")
        exit()

    info = {"name": "unknow", "version": "0.1"}
    with open(info_file, encoding="utf-8") as load_f:
        info = json.load(load_f)

    build_file = os.path.join(build_path, info["name"] + "_v" + info["version"] + ".aum")
    if os.path.exists(build_file):
        os.remove(build_file)

    with tarfile.open(build_file, mode="w:gz") as tar:
        tar.add(info_file, arcname=os.path.basename(info_file))
        for php_file in php_modules:
            tar.add(php_file, arcname=os.path.basename(php_file))
        tar.close()

    print("已经打包插件至: " + build_file)
    print("打包操作已经完成。")
    print("************************************************")
