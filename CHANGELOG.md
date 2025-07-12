# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Initial release of CMS-Orbit Comments package
- Morph 관계를 통한 모든 모델에 댓글 기능 추가
- 계층형 댓글 시스템 (답글 기능)
- 이모지 기반 반응 시스템 (좋아요, 사랑해요, 웃겨요 등)
- 별점 평점 시스템 (반별점 지원, 카테고리별 평점)
- 이메일 및 데이터베이스 알림 시스템
- 게스트 댓글 작성 기능
- Honeypot 방식 스팸 방지
- 댓글 수정/삭제 권한 관리
- 완전한 Vue.js 컴포넌트 제공
- RESTful API 엔드포인트
- 설정 가능한 옵션들 (평점 카테고리, 반응 타입, 보안 설정 등)

### Features
- `HasComments` trait을 통한 쉬운 모델 통합
- 실시간 반응 토글 기능
- 평점 카테고리별 세분화된 평가
- 자동 알림 시스템 (새 댓글, 답글)
- 페이지네이션 지원
- 반응형 디자인
- 접근성 고려한 UI/UX

### Technical
- Laravel 11 호환성
- PHP 8.3+ 지원
- Vue 3 Composition API 사용
- Tailwind CSS 스타일링
- TypeScript 지원 준비
- 테스트 코드 구조 준비 