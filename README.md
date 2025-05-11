# 友链RSS聚合 WordPress插件

[![WordPress版本](https://img.shields.io/badge/WordPress-%E5%85%BC%E5%AE%B9-brightgreen)](https://wordpress.org/)
[![WordPress版本](https://img.shields.io/badge/argon-%E5%85%BC%E5%AE%B9-brightgreen)](https://wordpress.org/)
[![版本](https://img.shields.io/badge/Version-1.2-blue)](https://github.com/your-username/argon-rss-links/releases)

在WordPress argon主题文章或页面中优雅地展示友情链接的最新文章，支持缓存机制，性能优化。

## ✨ 功能特色

- 👉 通过短代码轻松集成到任何页面
- 🚀 高性能缓存机制，大大减轻服务器负担
- ⏱️ 可自定义缓存时间，平衡实时性与性能
- 🌐 正确处理不同时区的时间显示
- 📱 响应式设计，适配各种设备
- 🛠️ 简洁直观的后台管理界面

## 📸 插件预览

![预览图片](https://github.com/user-attachments/assets/5ff38ae3-8468-4f91-9de6-4ce825e83f1e)

![设置页面](https://github.com/user-attachments/assets/09e72217-dcac-4cc9-a370-3e1541e5e979)

## 📥 安装方法

1. 从[发布页面](https://github.com/Rao-Ruixian/argon-rss-links/releases)下载最新的ZIP文件
2. 登录WordPress管理后台，进入"插件 > 安装插件"
3. 点击"上传插件"，选择下载的ZIP文件并上传
4. 上传完成后，点击"启用插件"

## 📝 使用方法

1. 在文章或页面中插入以下短代码（保留yaya的名字鸭）：
   ```
   [yaya-links-rss]
   ```

2. 在WordPress后台的"链接"菜单中管理友情链接，确保添加友链时填写正确的RSS地址

3. 访问"设置 > 友链RSS聚合"页面调整缓存设置

## ⚙️ 配置选项

### 缓存设置
- **缓存时间**：设置数据缓存的有效期，默认3600秒（1小时）
- **手动清除缓存**：更新友链后可立即刷新缓存内容

## 💡 为什么选择本插件？

本插件从[yaya-plugins-for-argon](https://github.com/crowya/yaya-plugins-for-argon)项目优化而来，解决了原插件没有缓存机制导致的性能问题：

- 🐢 **优化前**：每次加载都会发送大量请求，页面加载时间超过20秒
- 🚀 **优化后**：使用缓存机制，仅在缓存过期时请求数据，加载时间显著缩短

## 📝 更新日志

### v1.2
- 修复时区问题，确保正确显示中国时间
- 优化时间处理逻辑
- 代码优化和界面改进

### v1.1
- 添加缓存机制，大幅提高性能
- 新增后台设置页面
- 添加缓存时间自定义功能
- 提高安全性和稳定性

## 🙏 致谢

- 感谢[鸦鸦](https://github.com/crowya)，原项目的创建者
- 感谢阿锋，贡献了链接处理功能
